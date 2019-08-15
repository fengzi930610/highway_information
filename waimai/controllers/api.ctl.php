<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019年8月8日
 * Time: 09点08分
 */
if(!defined('__CORE_DIR')){
    exit("Access Denied");
}
class Ctl_Api extends Ctl
{
    //定时获取开奖接口数据的接口
    public function get_lottery_data(){
        $link = "refresh:10;url=".$this->mklink('waimai/api-get_lottery_data');
    	$odds = $this->system->config->get('odds');
    	$data = json_decode(K::M('net/http')->https_request($odds['apiUrl']),true);//模拟请求获取数据,进行数据转化
    	if( $data ===NULL ){
            print_r('开奖结果为空,正在重新刷新。。。');
    		header($link);die;//接口未返回开奖结果数据,则10秒后再次请求
    	}
    	$type = $data['code'];
        $arr_lid = [];
    	foreach($data['data'] as $val){
    		$where = [
    			'type' => $type,
    			'opentimestamp' =>$val['opentimestamp'],
    			'opentime' => $val['opentime'],
    			'opencode' =>$val['opencode'],
    			'expect' =>$val['expect']
    		];
    		if( !$re = K::M('lottery/lottery')->find($where) ){
    			$where['dateline'] =__TIME;
                if( $lid = K::M('lottery/lottery')->create($where) ){
                    $arr_lid[$lid] = $lid;
                }
    			
    		}
    	}
        if( $arr_lid ){
            $this->rebate($arr_lid);
            print_r('开奖结果已刷新');
            header("refresh:60*18;url=".$this->mklink('waimai/api-get_lottery_data'));die;//如果刷出新一期的开奖结果,则18分钟后再次获取
        }else{
            print_r('开奖结果尚未刷新');
            header($link);die;//如果开奖结果没有刷新则10秒获取一次
        }
    	// var_dump($data);die;
    }
    
    /**
     * @param {Object} $lid 开奖结果处理
     */
    public function rebate($arr_lid){
    	$odds = $this->system->config->get('odds');
        
        $a = [];
        foreach( $arr_lid as $v){
            $data = K::M('lottery/lottery')->detail($v);
            $where = [
                'type' =>$data['type'],
                'expect' =>$data['expect'],
                ':SQL' => 'update_time is null',
            ];
            //获取本期的注单
            if( $bet_items=K::M('bet/bet')->items($where,null ,1 ,999999) ){
                $lottery = explode(",",$data['opencode']);//开奖结果
                
                $wan = (int)$lottery[0];//万位开奖号码
                $qian = (int)$lottery[1];//千位开奖号码
                $bai = (int)$lottery[2];//百位开奖号码
                $shi = (int)$lottery[3];//十位开奖号码
                $ge = (int)$lottery[4];//个位开奖号码
				// var_dump($bet_items);die;
                foreach ($bet_items as $val){
                    $re = json_decode($val['data']);
                    $update_data = [
                        'update_time' =>__TIME,
                        'win_account' => 0.00,
                    ];
                    switch ( $val['digit'] ) {
                        case '1':
                            if( $re[$ge] ){
                                $money = (float)$re[$ge]*(float)$odds["odds"];
                                $update_data["win_account"] = $money;
                                $update_data['rebate'] = $money*(-1)+((float)$val['total_money']);//返利金额为负值
                            }else{
                                $update_data['rebate'] = (float)$val['total_money'];//返利金额为正值
                            }
							// var_dump($update_data);die;
       //                      var_dump($re[$ge]);die;
                            $instr = '注单:'.$val["expect"].'-个位- 盈利金额:'.$update_data["win_account"].'元';
							$a = K::M('bet/bet')->update($val['bid'], $update_data);
                            $b = K::M('member/member')->update_money($val['uid'],$update_data["win_account"], $instr);
                            break;
                        case '10': 
                            if( $re[$shi] ){//中奖结果处理
                                $money = (float)$re[$shi]*(float)$odds["odds"];
                                $update_data["win_account"] = $money;
                                $update_data['rebate'] = $money*(-1)+((float)$val['total_money']);//返利金额为负值
                            }else{
                                $update_data['rebate'] = (float)$val['total_money'];//返利金额为正值
                            }
                            $instr = '注单:'.$val["expect"].'-十位- 盈利金额:'.$update_data["win_account"].'元';
							$a = K::M('bet/bet')->update($val['bid'], $update_data);
                            $b = K::M('member/member')->update_money($val['uid'], $update_data["win_account"], $instr);
                            break; 
                        case '100': 
                            if( $re[$bai] ){//中奖结果处理
                                $money = (float)$re[$bai]*(float)$odds["odds"];
                                $update_data["win_account"] = $money;
                                    $update_data['rebate'] = $money*(-1)+((float)$val['total_money']);//返利金额为负值
                            }else{
                                $update_data['rebate'] = (float)$val['total_money'];//返利金额为正值
                            }
                            $instr = '注单:'.$val["expect"].'-百位- 盈利金额:'.$update_data["win_account"].'元';
                            $b = K::M('member/member')->update_money($val['uid'],$update_data["win_account"], $instr);
                            $a = K::M('bet/bet')->update($val['bid'], $update_data);
                            break;
                        case '1000': 
                            if( $re[$qian] ){//中奖结果处理
                                $money = (float)$re[$qian]*(float)$odds["odds"];
                                $update_data["win_account"] = $money;
                                $update_data['rebate'] = $money*(-1)+((float)$val['total_money']);//返利金额为负值
                            }else{
                                $update_data['rebate'] = (float)$val['total_money'];//返利金额为正值
                            }
                            $instr = '注单:'.$val["expect"].'-千位- 盈利金额:'.$update_data["win_account"].'元';
                            $a = K::M('bet/bet')->update($val['bid'], $update_data);
                            $b = K::M('member/member')->update_money($val['uid'], $update_data["win_account"], $instr);
                            break; 
                        case '10000': 
                            if( $re[$wan] ){//中奖处理,中奖金额*赔率
                                $money = (float)$re[$wan]*(float)$odds["odds"];
                                $update_data["win_account"] = $money;
                                $update_data['rebate'] = $money*(-1)+((float)$val['total_money']);//返利金额为负值
                            }
                            else{
                                $update_data['rebate'] = (float)$val['total_money'];//返利金额为正值
                            }
                            $instr = '注单:'.$val["expect"].'-万位- 盈利金额:'.$update_data["win_account"].'元';
                            $a = K::M('bet/bet')->update($val['bid'], $update_data);
                            $b = K::M('member/member')->update_money($val['uid'], $update_data["win_account"], $instr);
                            break; 
                        default: 
                    }
                    // if( $val['digit']==10000 ){//万位开奖处理
                    //     
                    // }else if( $val['digit']==1000 ){//千位开奖处理
                    //     
                    // }else if( $val['digit']==100 ){//百位开奖处理
                    //     
                    // }else if( $val['digit']==10 ){//十位开奖处理
                    //     
                    // }else if( $val['digit']==1 ){//个位开奖处理
                    // }
                }
            }
        }
        return true;
    }
    
    //cqssc前端数据接口
    public function front_data(){
        $where = [
            'type' => 'cqssc'
        ];
        $data = [
            'code' => 'cqssc',
            'rows' => 5,
            'info' => '实时接口数据'
        ];
        if( $items = K::M('lottery/lottery')->items($where,null ,1 ,5) ){
            foreach($items as $k=>$v){
                unset($v['lid']);
                unset($v['dateline']);
                unset($v['type']);
                $data['data'][] = $v;
            }
        }
        $this->msgbox->json($data);
    }
    
    /**
     * 定时任务测试接口
     */
    public function test(){
        $data=[
            'mobile'=>15277073211,
            'content' =>'【】您的短信验证码是857260，该验证码3分钟有效',
            'sms' =>'56dx',
            'status' =>0,
        ];
        K::M('sms/log')->create($data);
        echo "执行成功";die;
    }
}

?>