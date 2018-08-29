<?php
/**
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/7/4
 * Time: 13:29
 */

namespace App\Support;


trait ArticlesTrait
{
    /**
     * 数据处理
     * @param $data
     * @param $count
     * @param $pages
     * @param $pageSize
     * @return array
     */
    public function getNews($data, $count, $pageSize, $pages)
    {
        $info = [];
        foreach ($data['list'] as $key=>$value) {
            $info[$key]['id']           = $value['id'];
            $info[$key]['title']        = $value['title'];
            $info[$key]['summary']      = $value['summary'];
            $info[$key]['time']         = date("H:i:s",$value['updated_at']);
            $info[$key]['month']        = date("M",$value['updated_at']);
            $info[$key]['day']          = date("d",$value['updated_at']);
            $info[$key]['date']         = date("Y-m-d",$value['updated_at']);
            $info[$key]['stick']        = $value['stick'];
        }

        $page['total']         = $count['data']['count'];
        $page['page_count']    = ceil($count['data']['count']/$pageSize);
        $page['current_page']  = empty($pages['page']) ? 1 : intval($pages['page']);

        return ['list' => $info,'page' => $page];

    }
}