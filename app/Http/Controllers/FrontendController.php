<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

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

    public function cart(Request $request)
    {
        $carts=Cart::with(['product.galleries'])->where('users_id', Auth::user()->id)->get();

        return view('pages.frontend.cart', compact('carts'));
    }
    

    public function success(Request $request)
    {
        return view('pages.frontend.success');
    }

}
