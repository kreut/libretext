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
        $json = '[{"relation":["delegate_permission/common.handle_all_urls"],"target":{"namespace":"android_app","package_name":"edu.ualr.adapt.clicker","sha256_cert_fingerprints":["97:BB:A2:CB:51:34:55:2E:0D:9A:79:32:02:94:45:1E:C6:3E:2E:2B:3D:3E:65:82:B6:91:D3:2A:4A:AF:66:8A"]}}]';
        return response($json, 200)
            ->header('Content-Type', 'application/json');

    }
}
