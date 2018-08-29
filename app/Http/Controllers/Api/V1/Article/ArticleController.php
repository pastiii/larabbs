<?php

namespace App\Http\Controllers\Api\V1\Article;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\BaseController;
use App\Services\ArticleService;
use App\Support\ArticlesTrait;

/**
 * Class ArticleController
 * @package App\Http\Controllers\Api\V1\Article
 * Created by PhpStorm.
 * User: zxq
 * Date: 2018/5/30
 * Time: 11:15
 */
class ArticleController extends BaseController
{
    use ArticlesTrait;

    /** @var ArticleService **/
    protected $articleService;

    /**
     * @return \Illuminate\Foundation\Application|mixed
     */
    protected function getUserService()
    {
        if (!isset($this->article)) {
            $this->articleService = app('article');
        }
        return $this->articleService;
    }

    /**
     * 新闻,公告列表
     * @param Request $request
     * @return array
     */
    public function getNewsList(Request $request)
    {
        $this->getUserService();
        $info = $this->validate($request, [
            'sort'       => 'required|string|in:desc,asc',
            'code'       => 'required|string',
            'page_size'  => 'required',
            'page'       => 'nullable',
        ]);

        //获取新闻,公告列表
        $pageSize = empty($info['page_size']) ? 10 : $info['page_size'];
        $article        = $this->articleService->getArticleList($info,$pageSize);
        //文章类型不存在
        if ($article['code'] == 404) {
            return $this->errors($this->code_num('ArticleEmpty'),__LINE__);
        }

        $article_count  = $this->articleService->getArticleCount($info['code']);

        if ($article_count['code'] == 404) {
            return $this->errors($this->code_num('ArticleEmpty'),__LINE__);
        }


        if ($article['code'] != 200 || $article_count['code'] != 200) {
            $code = $this->code_num('GetMsgFail');
            return $this->errors($code, __LINE__);
        }

        //数据处理
        $data = $this->GetNews($article['data'], $article_count, $pageSize, $info);
        return $this->response($data, 200);
    }

    /**
     * 文章详情
     * @param  Request $request
     * @return array
     */
    public function getDetails(Request $request)
    {
        $this->getUserService();
        $info = $this->validate($request, [
            'id'   => 'nullable|int',
            'code' => 'nullable|string',
        ]);

        //获取文章公告详情
        $data = $this->articleService->getDetails($info);

        if ($data === false) {
           return $this->errors($this->code_num('ParamError'),__LINE__);
        }

        if ($data['code'] != 200) {
            $code = $this->code_num('GetMsgFail');
            return $this->errors($code, __LINE__);
        }
        return $this->response($data['data'], 200);
    }
}
