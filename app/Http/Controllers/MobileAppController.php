<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MobileAppController extends Controller
{
    /**
     * @return Application|ResponseFactory|Response
     */
    public function appleAppSiteAssociation()
    {
        $json = '{"applinks":{"apps":[],"details":[{"appID":"P2D2XN8RQX.edu.ualr.adapt.clicker","paths":["/courses*"]}]}}';
        return response($json, 200)
            ->header('Content-Type', 'application/json');
    }

    public function androidAssetLink()
    {
        $json = '{"relation":["delegate_permission/common.handle_all_urls","delegate_permission/common.get_login_creds"],"target":{"namespace":"android_app","package_name":"edu.ualr.adapt.clicker.go","sha256_cert_fingerprints":["F2:0C:65:B9:A4:42:BF:D4:1B:D9:F0:72:A3:0C:02:D3:08:35:CC:D7:72:92:84:5D:C4:D0:78:5D:39:B0:A5:C4"]}}';
        return response($json, 200)
            ->header('Content-Type', 'application/json');

    }
}
