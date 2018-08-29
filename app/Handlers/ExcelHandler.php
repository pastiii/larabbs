<?php
/**
 * Created by PhpStorm.
 * User: zhl
 * Date: 2018/6/14
 * Time: 14:37
 */
namespace App\Handlers;

use Illuminate\Support\Collection;

class ExcelHandler
{
    /**
     * @param  $data array 数据
     * @param  $headName array 列名
     * @param  $fileName string 文件名
    */
    public function export($data,$headName=[],$fileName='')
    {
        if(!empty($headName) && is_array($headName)){
            array_unshift($data,$headName);
        }else{
            if(!empty($data)) array_unshift($data,array_keys($data[0]));
        }
        if(empty($fileName)) $fileName=date('YndHis',time()). random_int(0,99999);
        return (new Collection($data))->downloadExcel($fileName.'.csv', $writerType = 'Csv', $headings = false);
    }
}