<?php
namespace Overrides\IMSGlobal\LTI;

use App\LtiLaunch;


class Cache
{

    public function get_launch_data($launch_id)
    {  $ltiLaunch = new LtiLaunch();
        $lti_launch_info = $ltiLaunch->where('launch_id', $launch_id)->first();
        return json_decode( $lti_launch_info->jwt_body, true);
    }


    public function cache_nonce($nonce)
    {
        return $this;
        /* $this->cache['nonce'][$nonce] = true;
         $this->save_cache();
         return $this;*/
    }

    public function check_nonce($nonce)
    {
        return true;
        /* $this->load_cache();
         if (!isset($this->cache['nonce'][$nonce])) {
             return false;
         }
         return true;*/
    }


}

?>
