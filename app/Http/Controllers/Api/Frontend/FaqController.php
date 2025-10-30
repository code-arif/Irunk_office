<?php
namespace App\Http\Controllers\Api\Frontend;

use App\Helpers\Helper;
use App\Http\Controllers\Controller;
use App\Models\FAQ;
use App\Traits\ApiResponse;

class FaqController extends Controller{
    use ApiResponse;
    public function index()
    {
        $faq = FAQ::where('status', 'active')->get();

        return $this->success(
            $faq,
            'Incoming friend requests fetched successfully.'
        );
    }
}
