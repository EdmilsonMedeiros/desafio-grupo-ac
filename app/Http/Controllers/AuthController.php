<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\AuthController;
use App\Http\Requests\UserRegisterRequest;

class AuthController extends Controller
{
    /**
     * @group Auth
     * Realiza a autenticação do usuário.
     * 
     * @bodyParam email string required Email do usuário. (required)
     * @bodyParam password string required Senha do usuário. (required)
     * 
     * @response 200 {  
     *  "access_token": "token",
     *  "token_type": "bearer",
     *  "expires_in": 3600
     * }
     * 
     * @response 401 {
     *  "error": "Unauthorized"
     * }
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    /**
     * @group Auth
     * Retorna os dados do usuário autenticado.
     * 
     * @header Authorization Bearer {token}
     * 
     * @response 200 {
     *  "id": 1,
     *  "name": "John Doe",
     *  "email": "john.doe@example.com",
     *  "email_verified_at": "2021-01-01 00:00:00",
     *  "created_at": "2021-01-01 00:00:00",
     *  "updated_at": "2021-01-01 00:00:00"
     * }
     * 
     * @response 401 {
     *  "error": "Unauthorized"
     * }
     */
    public function me()
    {
        return response()->json(auth()->user());
    }

    /**
     * @group Auth
     * Encerra a sessão do usuário.
     * 
     * @header Authorization Bearer {token}
     * 
     * @response 200 {
     *  "message": "Successfully logged out"
     * }
     * 
     * @response 401 {
     *  "error": "Unauthorized"
     * }
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * @group Auth
     * Atualiza o token de acesso.
     * 
     * @header Authorization Bearer {token}
     * 
     * @response 200 {
     *  "access_token": "token",
     *  "token_type": "bearer",
     *  "expires_in": 3600
     * }
     * 
     * @response 401 {
     *  "error": "Unauthorized"
     * }
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * @group Auth
     * Cria um novo usuário.
     * 
     * @header Authorization Bearer {token}
     *
     * @bodyParam name string required Nome do usuário. (required)
     * @bodyParam email string required Email do usuário. (required)
     * @bodyParam password string required Senha do usuário. (required)
     *
     * @response 201 {  
     *  "message": "Usuário criado com sucesso",
     *  "user": {
     *      "id": 1,
     *      "name": "John Doe",
     *      "email": "john.doe@example.com",
     *      "created_at": "2021-01-01 00:00:00",
     *      "updated_at": "2021-01-01 00:00:00"
     *  }
     * }
     */
    public function register(UserRegisterRequest $request)
    {
        $user = User::create(array_merge(
            $request->validated(),
            ['password' => Hash::make($request->password)]
        ));

        return response()->json([
            'message'   => 'Usuário criado com sucesso',
            'user'      => $user,
        ], 201);
    }

    /**
     * Retorna o token de acesso.
     *
     * @param  string $token
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token'  => $token,
            'token_type'    => 'bearer',
            'expires_in'    => auth()->factory()->getTTL() * 60
        ]);
    }
}