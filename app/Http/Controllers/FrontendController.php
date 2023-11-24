<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use PhpParser\Node\Stmt\Return_;
use PHPUnit\Framework\MockObject\ReturnValueNotConfiguredException;
use Midtrans\Config;
use Midtrans\Snap;

class FrontendController extends Controller
{
    
    public function index(Request $request)
    {
        $products=Product::with(['galleries'])->latest()->limit(10)->get();

        // return ($products);
        return view('pages.frontend.index', compact('products'));
    }


    public function details(Request $request, $slug)
    {
        $products=Product::with(['galleries'])->where('slug', $slug)->firstOrFail();
        $recommendation=Product::with(['galleries'])->inRandomOrder()->limit(4)->get();
        return view('pages.frontend.details', compact('products','recommendation'));
    }

    public function cartAdd($id, Request $request)
    {   
            Cart::create([
            'users_id'=> Auth::user()->id,
            'products_id'=>$id,
        ]);

        return redirect()->route('cart');
    }

    public function cartDelete($id)
    {
        
        $item= Cart::findOrFail($id);
        $item->delete();
        return redirect('cart');
    }

    public function cart(Request $request)
    {
        $carts=Cart::with(['product.galleries'])->where('users_id', Auth::user()->id)->get();

        $city= $this->rajaOngkir_city();

        return view('pages.frontend.cart', compact('carts', 'city'));
    }

    public function nomer()
    {
        $tStamp=date('Y-m-d');
        // cek nomer urut transaksi
        $contoh = Transaction::where('created_at','LIKE' ,"%".$tStamp."%")->orderBy('created_at', 'desc')->limit(1)->get();
        
        $tgl =date("Ymd", strtotime($tStamp));
        // cek apakah sudah ada trnasaski sudah ada atau belum di tamggal sekarang
        if((count($contoh))){
            // jika pada tanggal belum pernah ada transaksi akan menambahkan 0+1
            $urut =($contoh[0]->transaction_code) ;
            $no = substr($urut , -4, 4);
            $no = (int)$no +1;
            $newKodeTransaksi = $tgl . sprintf("-"."%04s", $no);
            // echo $newKodeTransaksi;  
            $no_transaksi =$newKodeTransaksi;
        } else
        {
            // jika dalam suatu tanggal sudah ada transaksi dia akan menambahkan nomer trnasaksi nya +1
            $no=0+1;
            $nomer_baru= $tgl.sprintf("-"."%04s", $no);
            // echo $nomer_baru;
            $no_transaksi =$nomer_baru;
        }

        return $no_transaksi;
    }

    public function checkout(CheckoutRequest $request)
    {
        $data = $request->all();

        $mama= Transaction::where('id',3)->get();
       
        
        $nomer_invoice =$this->nomer(); 
        $kode="INV";

        $inv = $kode. $nomer_invoice;

        // get data dari Cart per user
        $carts =Cart::with(['product'])->where('users_id', Auth::user()->id)->get();
        
        // add to transaction data
        $data['transaction_code']= $inv;
        $data['users_id']=Auth::user()->id;
        $data['total_price']=$carts->sum('product.price');

        // return $data;

        // create transaction
        $transaction =Transaction::create($data);

        // create transaction item
        foreach ($carts as $cart) {
            $items[]=TransactionItem::create([
                'transactions_id'=>$transaction->id,
                'users_id'=>$cart->users_id,
                'products_id'=>$cart->products_id,
                'transaction_code'=> $transaction->transaction_code,
            ]);
        }

        // hapus carts
        Cart::where('users_id', Auth::user()->id)->delete();

        // konfigurasi midtrans
        Config::$serverKey=config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSacitized');
        Config::$is3ds = config('services.midtrans.is3ds');

        // setup variable midtrans
        $midtrans=[
            'transaction_details' => [
                'order_id' => $inv,
                'gross_amount' => (int)$transaction->total_price,
            ],
            'customer_details'=>[
                'first_name'       => Auth::user()->name,
                // 'last_name'        => "Setiawan",
                'email'            => Auth::user()->email,
                'phone'            => "081322311801",
                'billing_address'  => $request->address,
                'shipping_address' => $request->address,
            ],
            'enabled_payments'=>['gopay', 'bank_transfer'],
            'vtweb'=>[]
        ];

        // payment proses
        try {
            // Get Snap Payment Page URL
            $paymentUrl = Snap::createTransaction($midtrans)->redirect_url;
                // simpan link url pembayaran ke tabel trnasaksi
            $transaction->payment_url= $paymentUrl;
            $transaction->save();

            // Redirect to Snap Payment Page
            return redirect($paymentUrl);
          }
          catch (Exception $e) {
            echo $e->getMessage();
          }
        
    }
    

    public function success(Request $request)
    {
        return view('pages.frontend.success');
    }



    public function rajaOngkir_city()
    {
        $response =Http::withHeaders([
            'key'=> '74e72558201e5c7db167c146420ab0dd'])->get('https://api.rajaongkir.com/starter/city');
            $kota= $response['rajaongkir']['results']   ;
        
            return $kota;
    }

    public function cek_rajaongkir(Request $request)
    {
        $responseCost =Http::withHeaders(['key'=> '74e72558201e5c7db167c146420ab0dd'])->post('https://api.rajaongkir.com/starter/cost',[
            // 'origin'=>$request->kota_asal,
            'origin'=> 151,
            'destination'=>$request->kota_tujuan,
            'weight'=> $request->berat,
            'courier'=>$request->kurir,
        ]);
    }

}
