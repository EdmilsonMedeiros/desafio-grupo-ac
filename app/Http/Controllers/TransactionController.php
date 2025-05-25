<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Requests\TransferRequest;
use App\Models\Transaction;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\DepositRequest;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    /**
     * @group Transaction
     * 
     * @header Authorization Bearer {token}
     * 
     * @response 200 [{
	 *  "id": 1,
	 *  "user_id": 1,
	 *  "beneficiary_id": 1,
	 *  "payer_name": "José Elias",
	 *  "amount": 100,
	 *  "type": "deposit",
	 *  "status": "success",
	 *  "created_at": "2025-05-24T18:19:52.000000Z",
	 *  "updated_at": "2025-05-24T18:19:52.000000Z",
	 *  "beneficiary": {
	 *      "id": 1,
	 *      "name": "Edmilson Jarbson",
	 *      "email": "edmilsonjhm@gmail.com",
	 *      "email_verified_at": null,
	 *      "created_at": "2025-05-24T17:25:59.000000Z",
	 *      "updated_at": "2025-05-24T17:25:59.000000Z"
	 *  }
	 * }]
     * 
     * @response 401 {
     *  "error": "Unauthorized"
     * }
     * */
    public function get(Request $request)
    {
        $user = $request->user();
        $transactions = $user->transactions;
        return response()->json($transactions);
    }

    /**
     * @group Transaction
     * 
     * @bodyParam email_beneficiary string required Email do usuário destino. (required)
     * @bodyParam amount float required Valor do depósito. (required)
     * @bodyParam payer_name string Nome do pagador. (required)
     * 
     * @response 200 {
     *  "message": "Depósito realizado com sucesso"
     * }
     * 
     * @response 400 {
     *  "message": "Saldo insuficiente"
     * }
     * 
     * @response 400 {
     *  "message": "A conta do usuário destino está com saldo negativo"
     * }
     * 
     * @response 500 {  
     *  "message": "Erro ao realizar depósito",
     *  "error": "Erro ao realizar depósito"
     * }
     * */
    public function depositSimulation(DepositRequest $request)
    {
        $beneficiary = User::where('email', $request->email_beneficiary)->first();
        $validateTransfer = $this->depositValidate($beneficiary, $request->amount, $request);

        if ($validateTransfer) {
            return $validateTransfer;
        }

        $actualBalance = $beneficiary->balance->balance;

        try{
            $beneficiary->balance->update([
                'balance' => $actualBalance + $request->amount
            ]);

            $beneficiary->transactions()->create([
                'amount'            => $request->amount,
                'type'              => 'deposit',
                'status'            => 'success',
                'user_id'           => $beneficiary->id,
                'beneficiary_id'    => $beneficiary->id,
                'payer_name'        => $request->payer_name ?? 'Deposito'
            ]);

            return response()->json(['message' => 'Depósito realizado com sucesso']);
        } catch (\Exception $e) {
            $beneficiary->balance->update([
                'balance' => $actualBalance
            ]);
            $beneficiary->transactions()->create([
                'amount'            => $request->amount,
                'type'              => 'deposit',
                'status'            => 'error',
                'user_id'           => $beneficiary->id,
                'beneficiary_id'    => $beneficiary->id,
                'payer_name'        => $request->payer_name ?? 'Deposito'
            ]);

            Log::channel('transactions_errors')->error('Erro ao realizar depósito', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Erro ao realizar depósito'], 500);
        }   
    }

    /**
     * @group Transaction
     * 
     * @bodyParam email_beneficiary string required Email do usuário destino. (required)
     * @bodyParam amount float required Valor da transferência. (required)
     * 
     * @header Authorization Bearer {token}
     * 
     * @response 200 {
     *  "message": "Transferência realizada com sucesso"
     * }
     * 
     * @response 400 {
     *  "message": "Saldo insuficiente"
     * }
     * 
     * @response 400 {
     *  "message": "Não é possível transferir para você mesmo"
     * }
     * 
     * @response 400 {
     *  "message": "A conta do usuário destino está com saldo negativo"
     * }
     * 
     * @response 500 {  
     *  "message": "Erro ao realizar transferência",
     *  "error": "Erro ao realizar transferência"
     * }
     * 
     * @response 401 {
     *  "error": "Unauthorized"
     * }
     * */
    public function transfer(TransferRequest $request)
    {
        $user = $request->user();
        $beneficiary = User::where('email', $request->email_beneficiary)->first();
        $validateTransfer = $this->transferValidate($user, $beneficiary, $request->amount);

        if ($validateTransfer) {
            return $validateTransfer;
        }

        $actualBalanceUser = $user->balance->balance;
        $actualBalanceBeneficiary = $beneficiary->balance->balance;

        try{
            $user->balance->update([
                'balance' => $actualBalanceUser - $request->amount
            ]);
            $beneficiary->balance->update([
                'balance' => $actualBalanceBeneficiary + $request->amount
            ]);
            $user->transactions()->create([
                'amount'            => $request->amount,
                'type'              => 'transfer',
                'status'            => 'success',
                'user_id'           => $user->id,
                'user_payer_id'     => $user->id,
                'beneficiary_id'    => $beneficiary->id,
                'payer_name'        => $user->name
            ]);

            $beneficiary->transactions()->create([
                'amount'            => $request->amount,
                'type'              => 'receive',
                'status'            => 'success',
                'user_id'           => $beneficiary->id,
                'user_payer_id'     => $user->id,
                'beneficiary_id'    => $beneficiary->id,
                'payer_name'        => $user->name
            ]);
            return response()->json(['message' => 'Transferência realizada com sucesso']);
        } catch (\Exception $e) {
            if($user->balance->balance !== $actualBalanceUser){
                $user->balance->update([
                    'balance' => $actualBalanceUser
                ]);
            }
            if($beneficiary->balance->balance !== $actualBalanceBeneficiary){
                $beneficiary->balance->update([
                    'balance' => $actualBalanceBeneficiary
                ]);
            }
            
            $user->transactions()->create([
                'amount'            => $request->amount,
                'type'              => 'transfer',
                'status'            => 'error',
                'user_id'           => $user->id,
                'user_payer_id'     => $user->id,
                'beneficiary_id'    => $beneficiary->id,
                'payer_name'        => $user->name
            ]);

            Log::channel('transactions_errors')->error('Erro ao realizar transferência', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Erro ao realizar transferência'], 500);
        }
    }

    /**
     * @group Transaction
     * 
     * @bodyParam transaction_id int required ID da transação. (required)
     * 
     * @header Authorization Bearer {token}
     * 
     * @response 200 {
     *  "message": "Transação revertida com sucesso"
     * }
     * 
     * @response 400 {
     *  "message": "ID da transação inválido"
     * }
     * 
     * @response 400 {
     *  "message": "Transação não pode ser revertida"
     * }
     * 
     * @response 400 {
     *  "message": "A transferência não pode ser revertida"
     * }
     * 
     * @response 400 {
     *  "message": "Reversão não permitida"
     * }
     * 
     * @response 400 {
     *  "message": "Saldo insuficiente para realizar a reversão"
     * }
     * 
     * @response 500 {
     *  "message": "Erro ao realizar reversão",
     *  "error": "Erro ao realizar reversão"
     * }
     * 
     * @response 401 {
     *  "error": "Unauthorized"
     * }
     */
    public function revert(Request $request){
        $validate = Validator::make($request->all(), [
            'transaction_id' => 'required|exists:transactions,id'
        ]);

        if($validate->fails()){
            return response()->json(['message' => 'ID da transação inválido'], 400);
        }

        $transaction = Transaction::find($request->transaction_id);
        $user = $request->user();
        $validateRevert = $this->revertValidate($transaction, $user);

        if ($validateRevert) {
            return $validateRevert;
        }

        $user                       = $transaction->user;
        $beneficiary                = $transaction->beneficiary;
        $userPayer                  = User::find($transaction->user_payer_id);
        $actualBalanceUser          = $user->balance->balance;
        $actualBalanceUserPayer     = $userPayer->balance->balance;

        if($actualBalanceUser < $transaction->amount) return response()->json(['message' => 'Saldo insuficiente para realizar a reversão'], 400);
        
        try{
            $user->balance->update([
                'balance' => $actualBalanceUser - $transaction->amount
            ]);
            $userPayer->balance->update([
                'balance' => $actualBalanceUserPayer + $transaction->amount
            ]);

            $user->transactions()->create([
                'amount'            => $transaction->amount,
                'type'              => 'revert',
                'status'            => 'success',
                'user_id'           => $user->id,
                'user_payer_id'     => $user->id,
                'beneficiary_id'    => $beneficiary->id,
                'payer_name'        => $user->name
            ]);

            $userPayer->transactions()->create([
                'amount'            => $transaction->amount,
                'type'              => 'revert',
                'status'            => 'success',
                'user_id'           => $userPayer->id,
                'user_payer_id'     => $userPayer->id,
                'beneficiary_id'    => $beneficiary->id,
                'payer_name'        => $user->name
            ]);

            $transaction->update([
                'status' => 'reverted'
            ]);

            return response()->json(['message' => 'Transação revertida com sucesso'], 200);
        }catch(\Exception $e){
            if($user->balance->balance !== $actualBalanceUser){
                $user->balance->update([
                    'balance' => $actualBalanceUser
                ]);
            }
            if($userPayer->balance->balance !== $actualBalanceUserPayer){
                $userPayer->balance->update([
                    'balance' => $actualBalanceUserPayer
                ]);
            }

            Log::channel('transactions_errors')->error('Erro ao realizar reversão', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Erro ao realizar reversão'], 500);
        }

        return response()->json(['message' => 'Transação revertida com sucesso'], 200);
    }

    /**
     * Valida a reversão da transação
     */
    public static function revertValidate($transaction, $user){
        if(!$transaction){
            return response()->json(['message' => 'Transferência não encontrada'], 404);
        }

        if($transaction->status !== 'success'){
            return response()->json(['message' => 'Transação não pode ser revertida'], 400);
        }

        if($transaction->type === 'transfer'){
            return response()->json(['message' => 'A transferência não pode ser revertida'], 400);
        }

        if($transaction->type === 'deposit'){
            return response()->json(['message' => 'O depósito não pode ser revertido'], 400);
        }

        // Verifica se faz parte da lista de transações do usuário
        if($transaction->user_id !== $user->id){
            return response()->json(['message' => 'Reversão não permitida'], 400);
        }

        return false;
    }

    /**
     * Valida transferências
     */
    public static function transferValidate($user, $beneficiary, $amount)
    {
        // Bloqueia se o usuário tiver transferência nos últimos 20 segundos
        $lastTransfer = $user->transactions()->where('type', 'transfer')->where('created_at', '>=', now()->subSeconds(20))->first();
        if ($lastTransfer) {
            return response()->json(['message' => 'Você não pode transferir novamente nos próximos 20 segundos'], 400);
        }

        // Verifica se o usuário tem saldo suficiente
        if ($user->balance->balance < $amount) {
            return response()->json(['message' => 'Saldo insuficiente'], 400);
        }

        // Bloqueia se o usuário estiver transferindo para ele mesmo
        if ($user->id === $beneficiary->id) {
            return response()->json(['message' => 'Não é possível transferir para você mesmo'], 400);
        }

        // Bloqueia se a conta do usuário destino estiver com saldo negativo
        if ($beneficiary->balance->balance < 0) {
            return response()->json(['message' => 'A conta do usuário destino está com saldo negativo'], 400);
        }

        return false;
    }

    /**
     * Valida depósitos
     */
    public static function depositValidate($beneficiary, $amount, $request)
    {
        // Bloqueia se a conta do usuário destino estiver com saldo negativo
        if ($beneficiary->balance->balance < 0) {
            return response()->json(['message' => 'A conta do usuário destino está com saldo negativo'], 400);
        }

        // Verifica se o usuário tem saldo suficiente
        if ($beneficiary->balance->balance < $amount) {
            return response()->json(['message' => 'Saldo insuficiente'], 400);
        }

        return false;
    }
}
