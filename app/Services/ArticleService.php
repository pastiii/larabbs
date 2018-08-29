<?php

namespace App\Services;
use App\Support\ApiRequestTrait;

/**
 * Class ArticleService
 * @package App\Services
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/5/30
 * Time: 11:15
 */
class ArticleService
{
    use ApiRequestTrait;
    
    protected  $adminBaseUrl;
    public function __construct()
    {
        $this->adminBaseUrl = env('ARTICLE_BASE_URL');
    }

    /**
     * 获取article列表
     * @param $info
     * @param $pageSize
     * @return array
     */
    public function getArticleList($info, $pageSize)
    {
        $page     = empty($info['page']) ? 1 : $info['page'];
        $start    = ($page-1)*$pageSize;
        $url = "article/posts_cate/code/".$info['code'];
        $cate_data=$this->send_request($url,'get', "",  $this->adminBaseUrl);

        if (empty($cate_data['data'])) {
            //没获取到分类
            return ['code'=>404];
        }

        if($cate_data['real_code'] ==200) {
            $url = "article/posts?stick=true&cate_id=" . $cate_data['data']['id'] . "&status=1&sort=id&order=" . $info['sort'] . "&start=" . $start . "&limit=" . $pageSize;
            return $this->send_request($url, 'get', "", $this->adminBaseUrl);
        }
        return $cate_data;
    }

    /**
     * 获取article总记录数
     * @param $code
     * @return array
     */
    public function getArticleCount($code)
    {
        $url = "article/posts_cate/code/".$code;
        $cate_data=$this->send_request($url,'get', "",  $this->adminBaseUrl);
        if (empty($cate_data['data'])) {
            //没获取到分类
            return ['code'=>404];
        }

        if($cate_data['real_code'] ==200) {
            $url = "article/posts/count?cate_id=" . $cate_data['data']['id'] . "&status=1";
            return $this->send_request($url, 'get', "", $this->adminBaseUrl);
        }
        return $cate_data;
    }

    /**
     * 获取news or notice详情
     * @param $info
     * @return array|bool
     */
    public function getDetails($info)
    {
        if (empty($info['id']) && empty($info['code'])) {
            return false;
        }

        if (!empty($info['id'])) {
            $url = "article/posts/id/".$info['id'];
            $detail_info=$this->send_request($url,'get', "",  $this->adminBaseUrl);
            //浏览量+1
            if($detail_info['real_code'] == 200){

                if ($detail_info['data']['status'] != 1) {
                    $detail_info['data']=[];
                }else{
                    $hits=[
                        'hits'=>$detail_info['data']['hits']+1
                    ];
                    $this->updataArticle($detail_info['data']['id'],$hits);
                }
            }
            return $detail_info;
        }

        if (!empty($info['code'])) {
            $url = "article/posts/code/".$info['code'];
            $detail_info=$this->send_request($url,'get', "",  $this->adminBaseUrl);
            //浏览量+1
            if($detail_info['real_code'] == 200){
                
                if ($detail_info['data']['status'] != 1) {
                    $detail_info['data']=[];
                }else{
                    $hits=[
                        'hits'=>$detail_info['data']['hits']+1
                    ];
                    $this->updataArticle($detail_info['data']['id'],$hits);
                }

            }

            return $detail_info;
        }
    }

    /**
     * 更新Article
     * @param $id int
     * @param $data array
     * @return array
     */
    public function updataArticle($id,$data){
        $url='article/posts/id/'.$id;
        return $this->send_request($url,'patch', $data,  $this->adminBaseUrl);
    }
}