<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionItem extends Model
{
    use HasFactory;

    protected $fillable=[
        'users_id',
        'products_id',
        'transactions_id',
        'transaction_code',
    ];


    /**
     * Get the user associated with the TransactionItem
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function product()
    {
        return $this->hasOne(Product::class, 'id', 'products_id');
    }
    


}
