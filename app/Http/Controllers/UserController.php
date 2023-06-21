<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function updateUser(Request $request)
    {
        try {
            DB::beginTransaction();
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'email' => 'required|email',
            ]);

            $user = User::where('email', $request->input('email'))->first();
            $user->name = $request->input('name');
            $user->save();

            DB::commit();

            return response()->json([
                'code' => 200,
                'status' => 'OK',
                'data' => 'success'
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            info('error update user', [$e->getMessage()]);
            return response()->json([
                'code' => 500,
                'status' => 'INTERNAL_SERVER_ERROR',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
