<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/12/21
 * Time: 9:23
 */
if(!defined('__CORE_DIR')){
    exit("Access Denied");
}
class Ctl_Index extends Ctl
{
    //index页面
    // public function index(){
    //     $this->tmpl='index.html';
    // }
	
	//进行搜索页验证后方可进入
    public function index_verify(){
        // var_dump(__IP);die;
        // // $this->cookie->set(__IP, 1, 60*15);
        // $cfg = $this->system->config->get('index_verify');
        // $data = [
        //     'check_ip' => __IP,
        //     'check_str' =>$cfg['verifystr'],
        //     'dateline' => '>:'.(int)(__TIME-20*60)
        // ];
        // 
        // if( !K::M('waimai/verify')->find($data) ){
        //     $link = $this->mklink('waimai/index');
        //     echo "今日验证已更新，请先进行验证,<a href='$link'>正在跳转</a>";
        //     header("refresh:3;url=".$link);die;
        // }
        // return true;
	}
    //qqssc页面
    public function cqssc()
    {
		$this->index_verify();
        if( $this->checksubmit() ){//下注单信息提交
            $type = [1,10,100,1000,10000];//个位,十位,百位,千位,万位
            if( $this->uid ){//用户已登录
				$items['uid'] = $this->uid;//下注会员;
                $items['digit'] = $this->GP('digit');//下注位数
                $items['data'] = $this->GP('data');//下注号码及金额
                $items['qs'] = $this->GP('qs');//下注期数
				$items['expect'] = date('Ymd').'0'.$items['qs'];//当前期数;
                $items['type'] = $this->GP('type');//下注彩票类型(快乐生肖，PK10，十一选五)
                if( K::M('lottery/lottery')->find(['expect'=>$items['expect'], 'type'=>'cqssc'])){
                    $this->msgbox->add('请确认下注期数',211)->response();
                }else if( $items['digit'] ===''  || !in_array((int)$items['digit'],$type) || empty($items['type'])){//如果下注位数为空,或者下注位数非类型数组中,或者参数未传
                    $this->msgbox->add('下注参数错误',211)->response();
                }else{
					foreach( $items['data'] as $v){
						if( (int)$v <0){
							$this->msgbox->add('下注金额参数不能小于0',211)->response();
						}
					}
                    $total_money = 0;//定义总金额
                    foreach($items['data'] as $val){
                        if($val){
                            $total_money += (int)$val;
                        }
                    }
                    if( $total_money <= 0){
                        $this->msgbox->add('请确认下注金额',211)->response();
                    }else{
						$this->system->db->begin();//开启事务
                        $items['dateline'] = __TIME;
                        $items['data'] = json_encode($items['data']);
                        $items['total_money'] = $total_money;
						if( ($bid=K::M('bet/bet')->create($items)) && (K::M('member/member')->update_money($this->uid, $items['total_money']*(-1), '支付注单:'.date('Y-m-d').' 0'.$items["qs"].'期数')) ){
							$detail = K::M('bet/bet')->detail($bid);
                            // var_dump($detail);die;
                            $digit = '';
                            switch ($detail['digit']) {
                                case 1: 
                                    $digit = '个位'; 
                                    break;
                                case 10: 
                                    $digit = '十位';
                                    break; 
                                case 100: 
                                    $digit = '百位';
                                    break;
                                case 1000: 
                                    $digit = '千位';
                                    break; 
                                case 10000: 
                                    $digit = '万位';
                                    break; 
                                default: 
                                
                            }
                            $this->system->db->commit();//事务提交
							$this->msgbox->add('下注成功，注单: '.date('Y-m-d').' 0'.$items["qs"].'期数 - '.$digit)->response();
						}else{
							$this->system->db->rollback();//失败则事务回滚
							$this->msgbox->add('下注失败')->response();
						}
                    }
                }
                
            }else{
                $this->pagedata['backurl'] = $this->mklink('waimai/passport/login');
                $this->msgbox->add('您尚未进行登录，请先登录')->response();
            }
        }
		if($this->uid){
            $win = K::M('bet/bet')->day_bet_account($this->uid, date("Ymd",strtotime("0 day")));
			$gain_today = $win[0]['yljiner']-$win[0]['jiner'];
            $this->pagedata['win'] = $gain_today;//传递用户信息
        }
        $this->pagedata['member'] = K::M('member/member')->detail($this->uid);//传递用户信息
		$this->pagedata['odds'] = $this->system->config->get('odds');//cqssc系统设置
        $this->tmpl='cqssc.html';
    }
    
    //PK10页面
    public function bjpk(){
        $this->index_verify();
        $this->pagedata['member'] = K::M('member/member')->detail($this->uid);//传递用户信息
        $this->tmpl = "bjpk10.html";
    }
    
    //gd11x5页面
    public function gd11x5(){
        $this->index_verify();
        $this->pagedata['member'] = K::M('member/member')->detail($this->uid);//传递用户信息
        $this->tmpl = "gd11x5.html";
    }
	
    public function jinjikg(){
		$this->index_verify();
        $this->tmpl = 'jinjikg.html';
    }
    
    public function index(){
        $cfg = $this->system->config->get('index_verify');
        if( $this->checksubmit() ){
            if( !$kw=$this->GP('kw') ){//如果kw对得上
                $this->msgbox->add('验证码不能为空', 211)->response();
            }else if( ($cfg = $this->system->config->get('index_verify'))!=null ){
                switch ($cfg['index_verify']) {
                    case '0':
                        $this->msgbox->add('请确认后台是否设置验证规则', 211)->response();
                        break;
                    case '1'://日期
                        if( $kw===date('Ymd') ){
                            // K::M('waimai/verify')->create(['check_ip'=>__IP, 'check_str'=>$kw, 'dateline'=>__TIME]);
                            $this->msgbox->add('验证成功')->response();
                        }else{
                            $this->msgbox->add('验证错误', 211)->response();
                        }
                        break;
                    case '2'://自定义字符
                        if( $kw===$cfg['verifystr']){
                            // K::M('waimai/verify')->create(['check_ip'=>__IP, 'check_str'=>$kw, 'dateline'=>__TIME]);
                            $this->msgbox->add('验证成功')->response();
                        }else{
                            $this->msgbox->add('验证错误', 211)->response();
                        }
                        break;
                }
            }else{
                $this->msgbox->add('今日验证码错误', 211)->response();
            }
        }else{
            $this->tmpl = 'sousuo.html';
        }
        
    }
    
}