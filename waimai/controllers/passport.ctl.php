<?php
/**
 * Copy Right IJH.CC
 * Each engineer has a duty to keep the code elegant
 * $Id: index.ctl.php 14351 2015-07-22 01:25:14Z wanglei $
 */

if(!defined('__CORE_DIR')){
    exit("Access Denied");
}

class Ctl_Passport extends Ctl
{
    public function index()
    {
        $this->login();
    }
    //授权登录
    public function login()
    {
        $this->pagedata['backurl'] = $this->mklink('index');
        if($this->uid){
            header("location:".$this->mklink('index/index'));
        }else{
            $this->tmpl = 'waimai/passport/login.html';
        }
        
    }

    //注册
    public function register()
    {
        if($this->checksubmit()){
            if(!$data['member_name'] = $this->GP('member_name')){
                $this->msgbox->add('请输入注册账号', 211);
            }else if(!K::M('verify/check')->member_name($data['member_name'])){
                $this->msgbox->add('请输入正确的注册账号', 211);
            }else if(!$data['passwd'] = $this->GP('passwd')){
                $this->msgbox->add('密码没有填写', 213);
            }else if($data['passwd'] !== $this->GP('repasswd')){
                $this->msgbox->add('两次输入的密码不一致', 215);
            }else if( K::M('member/member')->check_name($data['member_name']) ){
                $data['mobile'] = $this->GP('mobile');
                $data['Email'] = $this->GP('Email');
                $data['bank_account'] = $this->GP('bank_account');
                $data['bank_type'] = $this->GP('bank_type');
                $data['wx_account'] = $this->GP('wx_account');
                $data['qq_account'] = $this->GP('qq_account');
                $data['alipay_account'] = $this->GP('alipay_account');
                if($uid = K::M('member/account')->create($data)){
                    $this->msgbox->add('恭喜您，注册会员成功!');
                    $this->pagedata['backurl'] = $this->mklink('index');
                }else{
                    $this->msgbox->add('注册失败',216);
                }
            }else{
                $this->msgbox->add('注册失败',216);
            }
        }else{
            $this->tmpl = 'waimai/passport/register.html';
        }

    }

    //密码登录
    public function handle2()
	{
        $member_name = $this->GP('member_name');
        $password = $this->GP('password');

        if(!$a = K::M('verify/check')->member_name($member_name)){
            $this->msgbox->add('账号或密码有误', 212);
        }else if(!$password){
            $this->msgbox->add('账号或密码有误',213);
        }else if($member = K::M('member/member')->find(array('member_name'=>$member_name))){
            if($member['passwd'] == md5($password)){
                if($member = $this->auth->manager($member['uid'])){
                    $this->msgbox->add("欢迎您回来!");
                }
            }else{
                $this->msgbox->add("账号或密码有误!",215);
            }
        }else{
          $this->msgbox->add("账号或密码有误!",214);
        }

	}
    //退出登录
    public function loginout()
    {
        $this->auth->loginout();
        header("location:".$this->mklink('waimai/passport/login'));
    }
    //找回密码
    public function forget()
    {
        if($d=$this->GP('submit')){
            if(!K::M('member/member')->items(array('mobile'=>$d['mobile']))){
                $this->msgbox->add("手机号码不存在!",101);
            }/*elseif(K::M('system/session')->start()->get('code_'.$d['mobile']) != $d['verify']){
                $this->msgbox->add("短信验证码不正确!",101);
            }*/elseif(K::M('cache/cache')->get('code_'.$d['mobile']) != $d['verify']){
                $this->msgbox->add("短信验证码不正确!",101);
            }elseif(strlen($d['pswd'])<5){
                $this->msgbox->add("新密码至少6位!",101);
            }elseif(!K::M('member/member')->update_by_mobile($d['mobile'], array('passwd'=>md5($d['pswd'])))){
                $this->msgbox->add("未知错误,重置密码失败!",101);
            }else{
                $this->msgbox->add("重置密码成功!");
            }
        }else{
            $this->tmpl = "passport/forget.html";
        }
    }
    
    
    public function save(){
        if($this->checksubmit()){
            if( !($uid=$this->uid) ){
                echo "您未进行登录，请先登录。。。";
                header("location:".$this->mklink('waimai/passport/login'));
            }
            $type = $this->GP('type');
            if( $type==='grzx'){
                $data['member_name'] = $this->GP('member_name');
                $data['mobile'] = $this->GP('mobile');
                $data['bank_account'] = $this->GP('bank_account');
                $data['bank_type'] = $this->GP('bank_type');
                $data['wx_account'] = $this->GP('wx_account');
                $data['qq_account'] = $this->GP('qq_account');
                $data['alipay_account'] = $this->GP('alipay_account');
                if( !K::M('member/member')->find(['member_name'=>$data['member_name']]) ){
                    $this->msgbox->add('不能修改用户名',216)->response();
                }else if( !K::M('member/member')->find($data)){
                    K::M('member/member')->update($this->uid,$data);
                    $this->msgbox->add('修改成功')->response();
                }else{
                    $this->msgbox->add('请进行修改后提交')->response();
                }
            }else if($type==='grzxmm'){
                $data['passwd'] = $this->GP('passwd');
                $repasswd = $this->GP('repasswd');
                $data['pay_passwd'] = $this->GP('pay_passwd');
                $pay_repasswd = $this->GP('pay_repasswd');
                if( $data['passwd']!=$repasswd){
                    $this->msgbox->add('两次输入的登录密码不一致', 215)->response();
                }else if( $data['pay_passwd']!=$pay_repasswd){
                    $this->msgbox->add('两次输入的支付密码不一致', 215)->response();
                }else if(!$detail=K::M('member/member')->detail($this->uid)){
                    $this->msgbox->add('不存在的用户信息', 215)->response();
                }else if( $detail['passwd']!=$data['passwd'] ){
                    $data['passwd'] = md5($data['passwd']);
                }else if( $detail['paypasswd']!=$data['pay_passwd']){
                    $data['paypasswd'] = md5($data['pay_passwd']);
                }
                K::M('member/member')->update($this->uid,$data);
                $this->msgbox->add('修改成功')->response();
            }
            
        }
    }
}
