<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/6/14 0014
 * Time: 下午 3:44
 */

namespace app\controllers;

use app\models\PlatformOrders;
use Yii;

class PlatformSalesOrderController extends BaseController
{

    /**
     * fgetcsv 读取文件方法，读取CSV格式excel
     *导入各平台excel订单数据
     */
    public function actionSaveSalesOrders()
    {
        set_time_limit(0);
        $url = Yii::$app->basePath.'/web/platform-orders/wish.csv';
        $file = fopen($url,'r');
        $line_number = 0;

        /*$exstr=explode('.',$url)[0];
        $texstr=explode('/',$exstr)[3];
        $pordertables='pur_'.$texstr.'_orders';*/

        while ($datas = fgetcsv($file))
        {
            if($line_number == 0){ //跳过表头
                $line_number++;
                continue;
            }

            $num = count($datas);

            for($c=0; $c < $num; $c++){//读取表格每行数据（中文转码）
                $Name[$line_number][]  = iconv('gbk//ignore','utf-8',trim($datas[$c]));
            }

            $wstr=implode('',$Name[$line_number]);

            if($Name[$line_number][10]=='易佰深圳仓库' || empty($wstr)){
                unset($Name[$line_number]);
                continue;
            }


            if($Name[$line_number][3]=='否'){//是否补发货
                $Name[$line_number][3]='0';
            }else{
                $Name[$line_number][3]='1';
            }

            $line_number++;
        }
        var_dump($Name);
        die;

        //数据一次性入库
        $statu= Yii::$app->db->createCommand()->batchInsert(PlatformOrders::tableName(), ['sdate', 'stime', 'ship_name', 'rs_state', 'sku', 'qty', 'pro_weight', 'platform', 'account', 'sales_site', 'warehouse', 'parcel_number', 'mailing_way', 'total_weight', 'total_freight', 'tracking_number', 'order_number', 'item_id', 'item_title', 'buyer_id', 'buyer_name', 'country', 'shipping_address1', 'shipping_address2', 'city', 'province', 'zip_code', 'phone', 'mobile_phone', 'complete_address', 'payment_date', 'payment_time', 'sales_date', 'sales_time', 'receipt_paypal', 'payment_paypal', 'merchandiser', 'product_developer', 'inquirer', 'buyer', 'receiving_currency', 'order_total_price', 'rmb_order_total_price', 'price', 'rmb_price', 'commodity_cost', 'channel_transaction_currency', 'channel_payment_fee', 'rmb_channel_payment_fee', 'paypal_rate', 'paypal_fee', 'rmb_paypal_fee', 'channel_costs', 'first_way_of_transport', 'first_time_freight', 'headage_declaration_fee', 'packaging_materials', 'packaging_costs', 'freight', 'profit', 'profit_margins'], $Name)->execute();

        fclose($file);
        unset($Name);

        if($statu){
            $msg = '导入成功';
        } else {
            $msg = '导入失败';
        }

        header('Content-type:text/html;charset=utf-8');
        exit( "$msg" );

    }

    /**
     * phpexcel
     *导入各平台excel订单数据
     */
    public function actionSaveOrders(){
        echo '<pre>';
        set_time_limit(0);
        //设置的上传文件存放路径
        $file = Yii::$app->basePath.'/web/platform-orders/amazon.csv';
        //$file = Yii::$app->basePath.'/web/platform-orders/amazon1.csv';

        /*$filePath = Yii::$app->basePath.'/web/platform-orders/';
        if (!is_dir($filePath)) mkdir($filePath,0777,true);*/

        $str = "";
        //加载文件
        $path= "/Classes/PHPExcel/";
        set_include_path('.' . PATH_SEPARATOR . Yii::$app->basePath.$path . "PHPExcel.php" . PATH_SEPARATOR . get_include_path());

        require(Yii::getAlias("@phpexcel").'/Classes/PHPExcel.php');
        require(Yii::getAlias("@phpexcel").$path.'IOFactory.php');
        require(Yii::getAlias("@phpexcel").$path.'Reader/Excel2007.php');

        //require_once $path.'PHPExcel/Reader/Excel5.php';//excel 2003

        /*$filename=explode(".",$file);//把上传的文件名以“.”为准做一个数组。
        $time=date("Y-m-d-His");//去当前上传的时间
        $filename [0]=$time;//取文件名替换
        $name=implode (".",$filename); //上传后的文件名
        $uploadfile=$filePath.$name;//上传后的文件名地址*/


        //将上传的文件移动到新位置。若成功，则返回 true，否则返回 false。
        //$result=move_uploaded_file($filetempname,$uploadfile);

        if($result=1) //执行导入 excel操作
        {
            // $objReader = PHPExcel_IOFactory::createReader('Excel5');//use excel2003
            \PHPExcel_IOFactory::createReader('Excel2007');//use excel2003 和  2007 format
            $objPHPExcel = \PHPExcel_IOFactory::load($file);
           /* $sheet = $objPHPExcel->getSheet(0);
            $highestRow = $sheet->getHighestRow(); // 取得总行数
            $highestColumn = $sheet->getHighestColumn(); // 取得总列
            $total_num=\PHPExcel_Cell::columnIndexFromString($highestColumn);//由列名转为列数('AB'->28)*/

            $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);

            var_dump($sheetData);die;

            if($highestRow>=2){
                //循环读取excel文件,读取一条,插入一条
                for($j=2;$j<=$highestRow;$j++)
                {
                    for($k=0;$k<$total_num;$k++)
                    {
                        $col_name = \PHPExcel_Cell::stringFromColumnIndex($k);//由列数反转列名(0->'A')
                        //$str[$k]= mb_convert_encoding($sheet->getCell($col_name . $j)->getValue(), ''gbk', 'utf8');//转码,读取单元格
                        $str[$k]= $objPHPExcel->getActiveSheet()->getCell($col_name.$j)->getValue();//读取单元格

                    }

                    if($str[3]=='否'){//是否补发货
                        $str[3]='0';
                    }else{
                        $str[3]='1';
                    }

                    $orderdatas[]=$str;
                }

                var_dump($orderdatas);die;

                $statu= Yii::$app->db->createCommand()->batchInsert(AmazonOrders::tableName(), ['sdate', 'stime', 'ship_name', 'rs_state', 'sku', 'qty', 'pro_weight', 'platform', 'account', 'sales_site', 'warehouse', 'parcel_number', 'mailing_way', 'total_weight', 'total_freight', 'tracking_number', 'order_number', 'item_id', 'item_title', 'buyer_id', 'buyer_name', 'country', 'shipping_address1', 'shipping_address2', 'city', 'province', 'zip_code', 'phone', 'mobile_phone', 'complete_address', 'payment_date', 'payment_time', 'sales_date', 'sales_time', 'receipt_paypal', 'payment_paypal', 'merchandiser', 'product_developer', 'inquirer', 'buyer', 'receiving_currency', 'order_total_price', 'rmb_order_total_price', 'price', 'rmb_price', 'commodity_cost', 'channel_transaction_currency', 'channel_payment_fee', 'rmb_channel_payment_fee', 'paypal_rate', 'paypal_fee', 'rmb_paypal_fee', 'channel_costs', 'first_way_of_transport', 'first_time_freight', 'headage_declaration_fee', 'packaging_materials', 'packaging_costs', 'freight', 'profit', 'profit_margins'], $orderdatas)->execute();
                unset($orderdatas);
                if($statu){
                    $msg = '导入成功';
                } else {
                    $msg = '导入失败';
                }
            }

            //unlink ($uploadfile); //删除上传的excel文件
        }else{
            $msg = "没有数据！";
            //unlink ($uploadfile); //删除上传的excel文件
        }
        header('Content-type:text/html;charset=utf-8');
        exit( "$msg" );

    }


    //统计海外仓，一天的相同sku、仓库、平台的订单销量
    public function actionOrderReport(){
        set_time_limit(0);
        for($i=3; $i<=90; $i++){
            $ntime=date('Y-m-d',time()-86400*$i);

            $sql="select `platform`, `sku`, `warehouse` , `sdate`, sum(`qty`) from `pur_platform_orders` where `sdate`='".$ntime."' group by `sku`, `platform`, `warehouse`";
            $countsales=Yii::$app->db->createCommand($sql)->queryAll();

            $countsaless[]=$countsales;


        }


        foreach ($countsaless as $k=>$v){
            foreach ($v as $key=>$val){
                $formartval[]=array_values($val);
            }

        }
        echo '<pre>';
        var_dump($formartval);die;

        Yii::$app->db->createCommand()->batchInsert('pur_platform_sales_statistics',['platform','sku','warehouse_name','statistics_date','days_sales_1'],$formartval)->execute();

        echo date('Y-m-d H:i:s')." success..........";exit;
    }

}