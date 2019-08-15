<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/08/01
 * Time: 9:23
 * 头部导航
 */
if(!defined('__CORE_DIR')){
    exit("Access Denied");
}

class Ctl_Nav extends Ctl
{
    public function check_login(){
        if( !$this->uid ){
            $link = $this->mklink('waimai/passport-login');
            echo "您尚未登录，请先登录,<a href='$link'>正在跳转</a>";
            header("refresh:3;url=".$link);die;
        }
    }
    
    //下注状况
    public function xzzk() {
        $this->check_login();
        $today = date("Ymd",strtotime("0 day"));
        $data = K::M('bet/bet')->day_bet_account($this->uid, $today);
        //$data得到的是返利的基数 按会员等级算返利的金额
        $odds = $this->system->config->get('odds');
        $rebate_scale = $odds['rebate_0'];//默认返利比例
        //会员等级
        $level = $this->MEMBER['level'];
        switch ($level){
            case '1':
                $rebate_scale = $odds['rebate_1'];//代理返利比例
                break;
            case '2':
                $rebate_scale = $odds['rebate_2'];//VIP返利比例
                break;
        }
        //返利基数处理生成返利具体金额
        foreach ($data as $k=>$v){
            $data[$k]['fljiner'] = $data[$k]['fljiner']*(float)$rebate_scale*(0.01);
        }
        //总计计算
        $data[7] = [
            'time' => '总计',
            'jiner' => $data[0]['jiner']+$data[1]['jiner']+$data[2]['jiner']+$data[3]['jiner']+$data[4]['jiner']+$data[5]['jiner']+$data[6]['jiner'],
            'yxjiner' => $data[0]['yxjiner']+$data[1]['yxjiner']+$data[2]['yxjiner']+$data[3]['yxjiner']+$data[4]['yxjiner']+$data[5]['yxjiner']+$data[6]['yxjiner'],
            'yljiner' => $data[0]['yljiner']+$data[1]['yljiner']+$data[2]['yljiner']+$data[3]['yljiner']+$data[4]['yljiner']+$data[5]['yljiner']+$data[6]['yljiner'],
            'fljiner' =>$data[0]['fljiner']+$data[1]['fljiner']+$data[2]['fljiner']+$data[3]['fljiner']+$data[4]['fljiner']+$data[5]['fljiner']+$data[6]['fljiner'],
        ];
        $this->pagedata['data'] = $data;
        $this->tmpl='nav/xzzk.html';
    }
    
    //账户历史
    public function zhls(){
        $this->check_login();
        $data = K::M('bet/bet')->account_history($this->uid);
        
        //======================后期可能不是这么统计==========================
        $odds = $this->system->config->get('odds');
        $rebate_scale = $odds['rebate_0'];//默认返利比例
        //会员等级
        $level = $this->MEMBER['level'];
        switch ($level){
            case '1':
                $rebate_scale = $odds['rebate_1'];//代理返利比例
                break;
            case '2':
                $rebate_scale = $odds['rebate_2'];//VIP返利比例
                break;
        }
        //返利基数处理生成返利具体金额
        foreach ($data as $k=>$v){
            $data[$k]['fljiner'] = $data[$k]['fljiner']*(float)$rebate_scale*(0.01);
        }
        //========================================================================
        
        //总计计算
        $data['total'] = [
            'type' => '总计',
            'jiner' => $data['cqssc']['jiner']+$data['pk10']['jiner']+$data['syw']['jiner'],
            'yxjiner' => $data['cqssc']['yxjiner']+$data['pk10']['yxjiner']+$data['syw']['yxjiner'],
            'yljiner' => $data['cqssc']['yljiner']+$data['pk10']['yljiner']+$data['syw']['yljiner'],
            'fljiner' =>$data['cqssc']['fljiner']+$data['pk10']['fljiner']+$data['syw']['fljiner'],
        ];
        $this->pagedata['data'] = $data;
        $this->tmpl='nav/zhls.html';
		
    }
    
    //规则说明
    public function xzgz(){
        $this->tmpl='nav/xzgz.html';
    }
    
    //开奖结果
    public function kjjg(){
        $this->tmpl='nav/kjjg.html';
    }
    
    //系统公告
    public function xtgg(){
        $this->tmpl='nav/xtgg.html';
    }
}