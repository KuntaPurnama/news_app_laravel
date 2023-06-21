<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConfirmEmail;
use App\Mail\ResetPasswordMail;
use App\Models\PasswordResetToken;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cookie;

class AuthController extends Controller
{
    use HasApiTokens;

    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|min:8'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'BAD_REQUEST',
                    'error' => $validator->errors()->first()
                ], 400);
            }

            $credentials = $request->only('email', 'password');
            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                if ($user->email_verified_at == null) {
                    return response()->json([
                        'code' => 400,
                        'status' => 'OK',
                        'error' => 'Account Has Not Been Activated'
                    ], 400);
                }

                $user->auth_token = Str::uuid();
                $user->save();
                return response()->json([
                    'code' => 200,
                    'status' => 'OK',
                    'data' => 'success'
                ], 200)->cookie('token', $user->auth_token, 60, '/', 'localhost', false, false);
            }

            return response()->json([
                'code' => 400,
                'status' => 'INTERNAL_SERVER_ERROR',
                'error' => 'Invalid Email Or Password'
            ], 400);
        } catch (Exception $exception) {
            return response()->json([
                'code' => 500,
                'status' => 'INTERNAL_SERVER_ERROR',
                'error' => $exception->getMessage()
            ], 500);
        }
    }

    public function register(Request $request): JsonResponse
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8',
                'confirmPassword' => 'required|same:password'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'BAD_REQUEST',
                    'error' => $validator->errors()->first()
                ], 400);
            }

            $user = User::create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'confirmation_token' => Str::uuid(),
            ]);
            $baseUrl = env('FRONT_END_URL');
            $confirmationUrl = $baseUrl . '/activate-account/' . $user->confirmation_token;
            Mail::to($user->email)->send(new ConfirmEmail($confirmationUrl));
            DB::commit();
            return response()->json([
                'code' => 200,
                'status' => 'OK',
                'data' => 'success'
            ], 200);
        } catch (Exception $exception) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'status' => 'INTERNAL_SERVER_ERROR',
                'error' => $exception->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {

        try {
            $token = $request->input('token');
            DB::beginTransaction();
            $user = User::where('auth_token', $token)->first();

            $cookie = Cookie::forget('token');
            $user->auth_token = null;
            $user->save();

            DB::commit();

            return response()->json([
                'code' => 200,
                'status' => 'OK',
                'data' => true
            ], 200)->withCookie($cookie);
        } catch (Exception $exception) {
            DB::rollBack();
            return response()->json([
                'code' => 500,
                'status' => 'INTERNAL_SERVER_ERROR',
                'error' => $exception->getMessage()
            ], 500);
        }
    }

    public function validateResetPasswordToken(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'BAD_REQUEST',
                    'error' => $validator->errors()->first()
                ], 400);
            }
            $token = $request->input('token');
            $resetToken = PasswordResetToken::where('token', $token)->first();

            $createdDateTime = Carbon::parse($resetToken->created_at);
            $currentDateTime = Carbon::now();
            $timeDifferenceInMinutes = $currentDateTime->diffInMinutes($createdDateTime);
            if ($timeDifferenceInMinutes > 5) {
                return response()->json([
                    'code' => 400,
                    'status' => 'BAD_REQUEST',
                    'error' => 'Expired Reset Password Token',
                ], 400);
            }

            return response()->json([
                'code' => 200,
                'status' => 'OK',
                'data' => $resetToken
            ], 200);
        } catch (Exception $e) {
            info('error validate token', [$e->getMessage()]);
            return response()->json([
                'code' => 500,
                'status' => 'INTERNAL_SERVER_ERROR',
                'error' => 'Invalid Reset Password Token',
            ], 500);
        }
    }

    public function forgetPasswordToken(Request $request)
    {
        try {
            DB::beginTransaction();
            $email = $request->input('email');
            $user = User::where('email', $email)->first();

            if ($user == null) {
                return response()->json([
                    'code' => 400,
                    'status' => 'BAD_REQUEST',
                    'error' => 'No Email Registered'
                ], 400);
            }

            $passwordReset = new PasswordResetToken();
            $passwordReset->email = $request->input('email');
            $passwordReset->token = Str::uuid();
            $passwordReset->save();

            $baseUrl = env('FRONT_END_URL');
            $url = $baseUrl . '/reset-forgot-password/' . $passwordReset->token;
            Mail::to($passwordReset->email)->send(new ResetPasswordMail($url));

            DB::commit();
            return response()->json([
                'code' => 200,
                'status' => 'OK',
                'data' => $passwordReset,
            ], 200);
        } catch (Exception $e) {
            info('error forget password', [$e->getMessage()]);
            return response()->json([
                'code' => 500,
                'status' => 'INTERNAL_SERVER_ERROR',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|min:8',
                'newPassword' => 'required|min:8',
                'confirmNewPassword' => 'required|same:newPassword'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'BAD_REQUEST',
                    'error' => $validator->errors()->first()
                ], 400);
            }

            $email = $request->input('email');
            $user = User::where('email', $email)->first();

            $email = $request->input('email');
            $currentPassword = $request->input('password');
            $isValid = Hash::check($currentPassword, $user->password);

            if (!$isValid) {
                return response()->json([
                    'code' => 400,
                    'status' => 'BAD_REQUEST',
                    'error' => 'Old Password is invalid'
                ], 400);
            }

            $newPassword = Hash::make($request->input('newPassword'));
            $user->password = $newPassword;
            $user->save();

            DB::commit();

            return response()->json([
                'code' => 200,
                'status' => 'OK',
                'data' => true
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            info('error reset password', [$e->getMessage()]);
            return response()->json([
                'code' => 500,
                'status' => 'INTERNAL_SERVER_ERROR',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function resetForgotPassword(Request $request)
    {
        try {
            DB::beginTransaction();

            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'newPassword' => 'required|min:8',
                'confirmNewPassword' => 'required|same:newPassword'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'BAD_REQUEST',
                    'error' => $validator->errors()->first()
                ], 400);
            }

            $email = $request->input('email');
            $user = User::where('email', $email)->first();

            $newPassword = Hash::make($request->input('newPassword'));
            $user->password = $newPassword;
            $user->save();

            DB::commit();

            return response()->json([
                'code' => 200,
                'status' => 'OK',
                'data' => true
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            info('error reset password', [$e->getMessage()]);
            return response()->json([
                'code' => 500,
                'status' => 'INTERNAL_SERVER_ERROR',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function activateAccount($token)
    {
        try {
            $user = User::where('confirmation_token', $token)->first();
            info('token', [$token]);
            if ($user == null) {
                return response()->json([
                    'code' => 400,
                    'status' => 'BAD_REQUEST',
                    'error' => 'Failed To Activate Account'
                ], 400);
            }

            $user->confirmation_token = null;
            $user->email_verified_at = now();
            $user->save();

            return response()->json([
                'code' => 200,
                'status' => 'OK',
                'data' => true
            ], 200);
        } catch (Exception $exception) {
            info('ERROR ', [$exception->getMessage()]);
            return response()->json([
                'code' => 500,
                'status' => 'INTERNAL_SERVER_ERROR',
                'error' => $exception->getMessage()
            ], 500);
        }
    }

    public function isLoggedIn($token)
    {
        $user = User::where('auth_token', $token)->first();
        if ($user != null) {
            return response()->json([
                'code' => 200,
                'status' => 'OK',
                'data' => $user
            ], 200);
        }

        return response()->json([
            'code' => 400,
            'status' => 'BAD_REQUEST'
        ], 400);
    }
}
