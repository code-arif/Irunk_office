<?php

namespace App\Http\Controllers\Api\Frontend\AddTocart;

use App\Models\Offer;
use App\Models\Product;
use App\Models\CartItem;
use App\Models\AddToCart;
use Illuminate\Http\Request;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use GPBMetadata\Google\Api\Resource;
use Nwidart\Modules\Process\Updater;
use App\Http\Resources\AddToCartResource;
use Illuminate\Support\Facades\Validator;

class AddToCartController extends Controller
{

    public function addToCart(Request $request)
    {
        try {
            $user = auth()->guard('api')->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'product_id'    => 'required|exists:products,id',
                'quantity'      => 'required|integer|min:1',
                'product_size'  => 'nullable|string',
                'product_color' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $product = Product::findOrFail($request->product_id);

            if ($product->user_id === $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You cannot add your own product to the cart.',
                ], 403);
            }

            DB::beginTransaction();

            // ✅ Check existing quantity in cart
            $cart = AddToCart::firstOrCreate(
                ['user_id' => $user->id],
                ['total_price' => 0, 'vat' => 0, 'quantity' => 0, 'created_at' => now(), 'updated_at' => now()]
            );

            $existingCartItem = CartItem::where('add_to_cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();

            $existingQuantity = (int) ($existingCartItem->quantity ?? 0);
            $requestedQuantity = (int) $request->quantity;
            $productStock = (int) $product->quantity;

            if (($existingQuantity + $requestedQuantity) > $productStock) {
                return response()->json([
                    'status' => false,
                    'message' => 'Requested quantity exceeds product stock.',
                    'available_stock' => $productStock - $existingQuantity
                ], 400);
            }


            // ✅ Add or update product in cart_items
            $cartItem = $this->cartItem($request, $user, $product, $requestedQuantity);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Product added to cart successfully.',
                'data' => $cartItem,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong while adding to cart.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    // protected function cartItem(Request $request, $user, $product, $quantity)
    // {

    //     $cart = AddToCart::firstOrCreate(
    //         ['user_id' => $user->id],

    //         [
    //             'total_price' => 0,
    //             'vat_total' => 0,
    //             'total_quantity' => 0,
    //             'created_at' => now(),
    //             'updated_at' => now()
    //         ]
    //     );

    //     // ✅ Check if product already exists in cart_items
    //     // $cartItem = CartItem::where('add_to_cart_id', $cart->id)
    //     //     ->where('product_id', $product->id)
    //     //     ->first();
    //     $cartItem = CartItem::where('add_to_cart_id', $cart->id)
    //         ->where('product_id', $product->id)
    //         ->where('product_size', $request->product_size ?? $product->sizes->first()->size ?? null)
    //         ->where('product_color', $request->product_color ?? $product->colors->first()->color ?? null)
    //         ->first();


    //     if ($cartItem) {
    //         $cartItem->quantity += $quantity;
    //     } else {

    //         $defaultSize  = $product->sizes->first()->size ?? null;
    //         $defaultColor = $product->colors->first()->color ?? null;

    //         $cartItem = new CartItem();
    //         $cartItem->add_to_cart_id    = $cart->id;
    //         $cartItem->user_id           = $user->id;
    //         $cartItem->product_id        = $product->id;
    //         $cartItem->seller_id         = $product->user_id;
    //         $cartItem->category_name     = $product->category->name ?? '';
    //         $cartItem->sub_category_name = $product->subcategory->name ?? '';
    //         $cartItem->title             = $product->title;
    //         $cartItem->description       = $product->description;
    //         $cartItem->brand             = $product->brand ?? '';
    //         $cartItem->price             = $product->price;
    //         $cartItem->product_size      = $request->product_size ?? $defaultSize;
    //         $cartItem->product_color     = $request->product_color ?? $defaultColor;
    //         $cartItem->product_image     = $product->images->first()->image ?? '';
    //         $cartItem->quantity          = $quantity;
    //     }

    //     $cartItem->save();

    //     // ✅ Calculate cart totals (price, VAT, quantity)
    //     $cartItems = CartItem::where('add_to_cart_id', $cart->id)->get();

    //     $totalPrice = 0;
    //     $totalVat = 0;
    //     $totalQuantity = 0;

    //     foreach ($cartItems as $item) {
    //         $vatRate = $product->vat > 0 ? $product->vat / 100 : 0;
    //         $itemVat = $item->price * $vatRate;
    //         $totalVat += $itemVat * $item->quantity;
    //         $totalPrice += $item->price * $item->quantity;
    //         $totalQuantity += $item->quantity;
    //     }
    //     $cart->total_price = $totalPrice + $totalVat;
    //     $cart->vat = $totalVat;
    //     $cart->quantity = $totalQuantity;
    //     $cart->save();

    //     return $cartItem;
    // }

    protected function cartItem(Request $request, $user, $product, $quantity)
    {
        $cart = AddToCart::firstOrCreate(
            ['user_id' => $user->id],
            [
                'total_price' => 0,
                'vat' => 0,
                'quantity' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );

        // ✅ Check for existing offer for this buyer and product
        $offer = Offer::where('product_id', $product->id)
            ->where('buyer_id', $user->id)
            ->first();

        // ✅ Determine size/color based on offer or request/product defaults
        $offerSize  = $offer ? $offer->selected_size  : ($request->product_size ?? $product->sizes->first()->size ?? null);
        $offerColor = $offer ? $offer->selected_color : ($request->product_color ?? $product->colors->first()->color ?? null);
        $priceToUse = $offer ? $offer->offer_price    : $product->price;

        // ✅ Find existing cart item with same product, size & color
        $existingCartItem = CartItem::where('add_to_cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->where('product_size', $offerSize)
            ->where('product_color', $offerColor)
            ->first();

        if ($existingCartItem) {
            // 🟢 If the product already exists in the cart with the same offer (or same size/color), just update quantity
            $existingCartItem->quantity += $quantity;

            // 🔸 If offer exists, ensure price is updated to offer price
            if ($offer) {
                $existingCartItem->price = $priceToUse;
            }

            $existingCartItem->save();
        } else {
            // 🟡 No matching cart item, so create a NEW one
            $cartItem = new CartItem();
            $cartItem->add_to_cart_id    = $cart->id;
            $cartItem->user_id           = $user->id;
            $cartItem->product_id        = $product->id;
            $cartItem->seller_id         = $product->user_id;
            $cartItem->category_name     = $product->category->name ?? '';
            $cartItem->sub_category_name = $product->subcategory->name ?? '';
            $cartItem->title             = $product->title;
            $cartItem->description       = $product->description;
            $cartItem->brand             = $product->brand ?? '';
            $cartItem->price             = $priceToUse; // ✅ Use offer price if exists
            $cartItem->product_size      = $offerSize;
            $cartItem->product_color     = $offerColor;
            $cartItem->product_image     = $product->images->first()->image ?? '';
            $cartItem->quantity          = $quantity;
            $cartItem->save();
        }

        // ✅ Recalculate totals after add/update
        $cartItems = CartItem::where('add_to_cart_id', $cart->id)->get();

        $totalPrice = 0;
        $totalVat = 0;
        $totalQuantity = 0;

        foreach ($cartItems as $item) {
            $vatRate = $product->vat > 0 ? $product->vat / 100 : 0;
            $itemVat = $item->price * $vatRate;
            $totalVat += $itemVat * $item->quantity;
            $totalPrice += $item->price * $item->quantity;
            $totalQuantity += $item->quantity;
        }

        $cart->total_price = $totalPrice + $totalVat;
        $cart->vat   = $totalVat;
        $cart->quantity = $totalQuantity;
        $cart->save();

        return $existingCartItem ?? $cartItem;
    }


    // show all add to cart item

    public function shoAllAddtoCart()
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 400);
        }

        $allItem = AddToCart::where('user_id', $user->id)->get();

        if (!$allItem) {
            return response()->json([
                'status'  => true,
                'message' => 'Cart Item not found!'
            ], 200);
        }

        $addTocartItem = AddToCart::with('cartItem')->where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        if (!$addTocartItem) {
            return response()->json([
                'status'   => true,
                'message'  => 'Cart Item Empty!'
            ], 200);
        }

        return response()->json([
            'status' => true,
            'code'   => 200,
            'data'   => AddToCartResource::collection(
                $addTocartItem->sortByDesc('created_at')->values()
            )
        ]);
    }

    // add to cart item decrement

    public function cartItemDecrement(Request $request)
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $cartItemId = $request->input('cart_items_id');

        $validator = Validator::make(['cart_items_id' => $cartItemId], [
            'cart_items_id' => 'required|exists:cart_items,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        $cartItem = CartItem::where('id', $request->cart_items_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'status' => false,
                'message' => 'Cart item not found!'
            ], 404);
        }

        if ($cartItem->quantity > 1) {
            $cartItem->quantity -= 1;
            $cartItem->save();

            // Update cart totals
            $this->updateCartTotals($cartItem->add_to_cart_id);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Quantity cannot be less than 1'
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => 'Cart item quantity updated successfully',
            'data' => [
                'cart_item_id' => $cartItem->id,
                'quantity'     => $cartItem->quantity,
            ],
        ]);
    }

    protected function updateCartTotals($cartId)
    {
        $cart = AddToCart::find($cartId);
        if (!$cart) return;

        $cartItems = $cart->cartItem;

        $totalPrice = 0;
        $totalVat = 0;
        $totalQuantity = 0;

        foreach ($cartItems as $item) {
            $vatRate = $item->product->vat > 0 ? $item->product->vat / 100 : 0;
            $itemVat = $item->price * $vatRate;
            $totalVat += $itemVat * $item->quantity;
            $totalPrice += $item->price * $item->quantity;
            $totalQuantity += $item->quantity;
        }

        $cart->total_price = $totalPrice + $totalVat;  // total including VAT
        $cart->vat = $totalVat;                 // correct column
        $cart->quantity = $totalQuantity;       // correct column
        $cart->save();
    }



    // cart item increment

    public function cartItemIncrement(Request $request)
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'cart_items_id' => 'required|exists:cart_items,id',
            'quantity'      => 'nullable|integer|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        $cartItem = CartItem::where('id', $request->cart_items_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'status' => false,
                'message' => 'Cart item not found!'
            ], 404);
        }

        $increment = $request->quantity ?? 1;

        // Check product stock
        $productStock = $cartItem->product->quantity ?? 0;
        if (($cartItem->quantity + $increment) > $productStock) {
            return response()->json([
                'status' => false,
                'message' => 'Requested quantity exceeds available stock'
            ], 400);
        }
        $cartItem->quantity += $increment;
        $cartItem->save();

        // Update cart totals
        $this->updateCartTotals($cartItem->add_to_cart_id);

        return response()->json([
            'status' => true,
            'message' => 'Cart item quantity updated successfully',
            'data' => [
                'cart_item_id' => $cartItem->id,
                'quantity'     => $cartItem->quantity,
            ],
        ]);
    }

    // delete cart item

    public function deleteItem(Request $request)
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'cart_items_id' => 'required|exists:cart_items,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        $cartItem = CartItem::where('id', $request->cart_items_id)
            ->where('user_id', $user->id) // ✅ ensure item belongs to this user
            ->first();

        if (!$cartItem) {
            return response()->json([
                'status' => false,
                'message' => 'Cart item not found'
            ], 404);
        }

        // ✅ keep the cart_id before deleting
        $cartId = $cartItem->add_to_cart_id;

        $cartItem->delete();

        // ✅ safely update totals
        $this->updateCartTotals($cartId);

        $cart = AddToCart::with('cartItem.product')->find($cartId);

        return response()->json([
            'status'  => true,
            'message' => 'Item removed from cart successfully',
            // 'cart'    => $cart // refreshed cart with updated totals
        ]);
    }

    // Remove all cart

    public function emptyCart(Request $request)
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $cartId = $request->input('cart_id');

        $validator = Validator::make(['cart_id' => $cartId], [
            'cart_id' => 'required|exists:add_to_carts,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first()
            ], 400);
        }

        $addToCart = AddToCart::where('id', $cartId)->first();
        if (!$addToCart) {
            return response()->json([
                'status' => false,
                'message' => 'Add to cart already empty'
            ], 401);
        }

        $addToCart->delete();

        return response()->json([
            'status'   => true,
            'code'     => 200,
            'message'  => 'Add to cart successfully empty!'
        ]);
    }
}
