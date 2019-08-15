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

class Ctl_Member extends Ctl
{
    public function check_login(){
        if( !$this->uid ){
            $link = $this->mklink('waimai/passport-login');
            echo "您尚未登录，请先登录,<a href='$link'>正在跳转</a>";
            header("refresh:3;url=".$link);die;
        }else{
            $this->pagedata['member'] = $this->MEMBER;//传递用户信息
        }
    }
    
    //会员个人中心
    public function grzx()
    {
        $this->check_login();
        $this->tmpl = 'waimai/member/grzx.html';
    }
    //会员个人中心密码设置
    public function grzxmm(){
        $this->check_login();
        $this->tmpl = 'waimai/member/grzxmm.html';
    }
    
    //账户充值
    public function recharge()
    {
        $this->tmpl = "";
    }
    
    //申请提现
    public function apply(){
        $this->check_login();
        if($data = $this->checksubmit('data')){
            $tixian = K::M('member/tixian')->find(array('uid'=>$this->uid),array('tixian_id'=>'DESC'));
            $ltime = $tixian['dateline'] +($cfg_tixian['day']*86400);
            if(!$money = (float)$data['money']){
                $this->msgbox->add('金额非法',202);
            }else if($money<=0){
                $this->msgbox->add('金额非法',206);
            }else if($money>$this->MEMBER['money']){
                $this->msgbox->add('提现金额不能大于余额',203);
            }else if(!$this->system->config->get('tixian')['member']){
                $this->msgbox->add('平台未开启用户提现功能',204);
            }else if($money<$cfg_tixian['limit']){
                $this->msgbox->add('提现金额金额最低'.$cfg_tixian['limit'].'元',205);
            }else if($ltime>__TIME){
                $this->msgbox->add('距离上次提现不足'.$cfg_tixian['day'].'天',206);
            }else if(!$data['intro']){
                $this->msgbox->add('请填写提现信息',205);
            }else if(K::M('member/member')->detail($this->uid)['paypasswd']!=md5($data['paypasswd'])){
                $this->msgbox->add('支付密码错误',207)->response();
            }else if(!K::M('cache/cache')->islock('member_tixian'.$this->uid,3)){
                $this->msgbox->add('处理中..',207)->response();
            } else{
                $insert_data = array();
                $insert_data['uid'] = $this->uid;
                $insert_data['money'] = $money;
                $insert_data['intro'] = $data['intro'];
                $insert_data['status'] = 0;
                if(K::M('member/tixian')->tixian($this->uid,$insert_data)){
                    K::M('cache/cache')->unlock('member_tixian'.$this->uid);
                    $this->msgbox->add("提现申请成功");
                }else{
                    $this->msgbox->add("提现申请失败",206);
                }
            }
        }else{
            $this->msgbox->add('非法数据请求',201);
        }
    
    }
    
    public function money($page=1)
    {
        $this->check_login();
        if($rebackurl = $this->GP('rebackurl')){
            $this->pagedata['rebackurl'] = $rebackurl;
        }
        $this->pagedata['is_allow'] = $this->system->config->get('tixian')['member']?1:0;
        $data = $this->ftrst_html(1);
        $this->pagedata['data'] = $data;
        $this->tmpl = "waimai/member/money.html";
    }
    
    //加载用户花费明细
    public function loaditems($page = 1){
        $this->check_login();
        $filter = array();
        $filter['uid'] = $this->uid;
        $filter['type'] = 'money';
        $count = 0;
        $pager['limit'] = $limit = 30;
        $pager['page'] = $page = max((int) $page, 1);
        if(!$items = K::M('member/log')->items($filter, array('log_id'=>'DESC'), $page, $limit, $count)){
            $items = array();
        }
        if($count <= $limit){
            $loadst = 0;
        }else{
            $loadst = 1;
        }
        
        $this->msgbox->set_data('loadst', $loadst);
        $this->pagedata['pager'] = $pager;
        $this->pagedata['items'] = $items;
        $this->tmpl = "waimai/member/loaditems.html";
        $html = $this->output(true);
        $this->msgbox->set_data('html', $html);
        $this->msgbox->json();
    }
    
    public function ftrst_html($page=1){
        $this->check_login();
        $filter = array();
        $filter['uid'] = $this->uid;
        $filter['type'] = 'money';
        $count = 0;
        $pager['limit'] = $limit = 30;
        $pager['page'] = $page = max((int) $page, 1);
        if(!$items = K::M('member/log')->items($filter, array('log_id'=>'DESC'), $page, $limit, $count)){
            $items = array();
        }
        if($count <= $limit){
            $loadst = 0;
        }else{
            $loadst = 1;
        }
    
        $this->msgbox->set_data('loadst', $loadst);
        $this->pagedata['pager'] = $pager;
        $this->pagedata['items'] = $items;
        $this->tmpl = "waimai/member/loaditems.html";
        $html = $this->output(true);
        return array(
            'html'=>$html,
            'loadst'=>$loadst
        );
    
    }
}

?>