<?php
namespace lezaiyun\Leseo\BaiduSubmit;

use lezaiyun\Leseo\inc\Cache\LeCache;

class LeseoBaiduResponse {
    public $type          = 'normal';
    public $remain        = Null;          # 当天剩余的可推送url条数          当返回值为0时，再提交的数据不会添加
    public $success       = Null;          # 成功推送的url条数
    public $success_daily = Null;          # 已成功推送到快速收录的url条数
    public $remain_daily  = Null;          # 剩余可推送的快速收录的url条数     当返回值为0时，再提交的数据不会添加
    public $not_same_site = Null;          # [], 由于不是本站url而未处理的url列表
    public $not_valid     = Null;          # 不合法的url列表
    public $error         = Null;          # error 	是 	int 	错误码，与状态码相同
    public $message       = Null;          # message 	是 	string 	错误描述

    private $messages     = [
        'site error'                       => '站点未在站长平台验证',
        'empty content'                    => 'post内容为空',
        'only 2000 urls are allowed once'  => '每次最多只能提交2000条链接',
        'over quota'                       => '超过每日配额了，超配额后再提交都是无效的',
        'token is not valid'               => 'token错误',
        'not found'                        => '接口地址填写错误',
        'internal error, please try later' => '服务器偶然异常，通常重试就会成功',
    ];
    private $cache;
    
    public function __construct($resp, $type)
    {
        $this->type       = $type;
        $this->cache      = new LeCache('remain');
        if (property_exists($resp, 'error')) {
            $this->_handle_error($resp);
        } else {
            $this->_handle_success($resp);
        }
        # TODO: 集中不同的返回内容的返回值进行应对处理
    }

    public function _handle_success($resp){
        if (property_exists($resp, 'not_same_site')) {
            $this->not_same_site = $resp->not_same_site;
        }
        if (property_exists($resp, 'not_valid')) {
            $this->not_valid = $resp->not_valid;
        }

        if ($this->type == 'daily') {
            $this->success_daily = $resp->success_daily;
            $this->remain_daily  = $resp->remain_daily;
            $this->cache->set('remain_daily', $this->remain_daily);
        } else {
            $this->remain  = $resp->remain;
            $this->success = $resp->success;
            $this->cache->set('remain', $this->remain);
        }
    }

    public function _handle_error($resp){
        $this->error = $resp->error;
        $this->message = $this->messages[$resp->message];
    }
}


class LeoBaiduSubmitter {
    private $urls_temp_file;
    private $api_url = 'http://data.zz.baidu.com/urls?';
    private $daily_param = '&type=daily';
    private $token;
    private $api;

    public function __construct($options, $site)
    {
        $this->token = $options['token'];
        $this->api = $this->api_url . 'site=' . $site . '&token=' . $this->token;
    }

    public function request($type, $urls_array){
        $url = $this->api;
        if ('daily' == $type) {
            $url .= $this->daily_param;
        }
        $response = wp_safe_remote_post( $url, array(
            'httpversion' => '1.1',
            'headers'     => array('Content-Type: text/plain'),
            'body'        => implode('
			', $urls_array),
        ));
        if ( is_wp_error( $response ) ) {
            return $response;
        } else {
            return new LeseoBaiduResponse(json_decode($response['body']), $type);
        }
    }

    public function get_urls() {
        $urls_temp_file_path = dirname(__FILE__) . '/' . $this->urls_temp_file;
        if(file_exists($urls_temp_file_path)){
            $urls_string = file_get_contents($urls_temp_file_path);

        }
    }

}
