<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;

class ProductImageController extends Controller
{
    public function destroy($id)
    {
        $image = ProductImage::findOrFail($id);

        Storage::disk('public')->delete($image->image_path);

        $product = $image->product;
        $image->delete();

        if ($product && $product->primary_image_id == $id) {
            $product->update(['primary_image_id' => null]);
        }

        return back();
    }
}
