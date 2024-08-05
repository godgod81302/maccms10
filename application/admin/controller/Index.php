<?php
namespace app\admin\controller;
use think\Hook;
use think\Db;


class Index extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function login()
    {
        if(Request()->isPost()) {
            $data = input('post.');
            $res = model('Admin')->login($data);
            if ($res['code'] > 1) {
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }
        Hook::listen("admin_login_init", $this->request);
        return $this->fetch('admin@index/login');
    }

    public function logout()
    {
        $res = model('Admin')->logout();
        $this->redirect('index/login');
    }

    public function index()
    {
        $menus = @include MAC_ADMIN_COMM . 'auth.php';

        foreach($menus as $k1=>$v1){
            foreach($v1['sub'] as $k2=>$v2){
                if($v2['show'] == 1) {
                    if(strpos($v2['action'],'javascript')!==false){
                        $url = $v2['action'];
                    }
                    else {
                        $url = url('admin/' . $v2['controller'] . '/' . $v2['action']);
                    }
                    if (!empty($v2['param'])) {
                        $url .= '?' . $v2['param'];
                    }
                    if ($this->check_auth($v2['controller'], $v2['action'])) {
                        $menus[$k1]['sub'][$k2]['url'] = $url;
                    } else {
                        unset($menus[$k1]['sub'][$k2]);
                    }
                }
                else{
                    unset($menus[$k1]['sub'][$k2]);
                }
            }

            if(empty($menus[$k1]['sub'])){
                unset($menus[$k1]);
            }
        }

        $quickmenu = config('quickmenu');
        if(empty($quickmenu)){
            $quickmenu = mac_read_file( APP_PATH.'data/config/quickmenu.txt');
            $quickmenu = explode(chr(13),$quickmenu);
        }
        if(!empty($quickmenu)){
            $menus[1]['sub'][13] = ['name'=>lang('admin/index/quick_tit'), 'url'=>'javascript:void(0);return false;','controller'=>'', 'action'=>'' ];

            foreach($quickmenu as $k=>$v){
                if(empty($v)){
                    continue;
                }
                $one = explode(',',trim($v));
                if(substr($one[1],0,4)=='http' || substr($one[1],0,2)=='//'){

                }
                elseif(substr($one[1],0,1) =='/'){

                }
                elseif(strpos($one[1],'###')!==false || strpos($one[1],'javascript:')!==false){

                }
                else{
                    $one[1] = url($one[1]);
                }
                $menus[1]['sub'][14 + $k] = ['name'=>$one[0], 'url'=>$one[1],'controller'=>'', 'action'=>'' ];
            }
        }
        $this->assign('menus',$menus);
        $this->assign('title',lang('admin/index/title'));
        return $this->fetch('admin@index/index');
    }


    public function test()
    {   
        $menus = @include MAC_ADMIN_COMM . 'auth.php';

        foreach($menus as $k1=>$v1){
            $menus[$k1]['sub']['id']= $k1;
            $menus[$k1]['id'] = $k1;
            foreach($v1['sub'] as $k2=>$v2){
                $menus[$k1]['sub'][$k2]['parent_id'] = $k1;
                if($v2['show'] == 1) {
                    if(strpos($v2['action'],'javascript')!==false){
                        $url = $v2['action'];
                    }
                    else {
                        $url = url('admin/' . $v2['controller'] . '/' . $v2['action']);
                    }
                    if (!empty($v2['param'])) {
                        $url .= '?' . $v2['param'];
                    }
                    if ($this->check_auth($v2['controller'], $v2['action'])) {
                        $menus[$k1]['sub'][$k2]['url'] = $url;
                    } else {
                        unset($menus[$k1]['sub'][$k2]);
                    }
                }
                else{
                    unset($menus[$k1]['sub'][$k2]);
                }
            }

            if(empty($menus[$k1]['sub'])){
                unset($menus[$k1]);
            }
        }

        $quickmenu = config('quickmenu');

        if(empty($quickmenu)){
            $quickmenu = mac_read_file( APP_PATH.'data/config/quickmenu.txt');
            $quickmenu = explode(chr(13),$quickmenu);
        }
        if(!empty($quickmenu)){
            $menus[1]['sub'][13] = ['name'=>lang('admin/index/quick_tit'), 'url'=>'javascript:void(0);return false;','controller'=>'', 'action'=>'' ];

            foreach($quickmenu as $k=>$v){
                if(empty($v)){
                    continue;
                }
                $one = explode(',',trim($v));
                if(substr($one[1],0,4)=='http' || substr($one[1],0,2)=='//'){

                }
                elseif(substr($one[1],0,1) =='/'){

                }
                elseif(strpos($one[1],'###')!==false || strpos($one[1],'javascript:')!==false){

                }
                else{
                    $one[1] = url($one[1]);
                }
                $menus[1]['sub'][14 + $k] = ['parent_id'=>1,'name'=>$one[0], 'url'=>$one[1],'controller'=>'', 'action'=>'' ];
            }
        }
        $update_sql = file_exists('./application/data/update/database.php');

        $this->assign('update_sql',$update_sql);
        $this->assign('menus',$menus);
        $this->assign('title',lang('admin/index/title'));
        return $this->fetch('admin@index/test2');
    }


    public function welcome()
    {
        // print_r($this->botlist());exit;
        $version = config('version');
        $update_sql = file_exists('./application/data/update/database.php');

        $this->assign('spider_data',$this->botlist());
        $this->assign('os_data',$this->get_system_status());
        $this->assign('version',$version);
        $this->assign('update_sql',$update_sql);
        $this->assign('mac_lang',config('default_lang'));
        $this->assign('dashboard_data',$this->getAdminDashboardData());

        $this->assign('admin',$this->_admin);
        $this->assign('title',lang('admin/index/welcome/title'));
        return $this->fetch('admin@index/welcome');
    }

    public function quickmenu()
    {
        if(Request()->isPost()){
            $param = input();
            $validate = \think\Loader::validate('Token');
            if(!$validate->check($param)){
                return $this->error($validate->getError());
            }
            $quickmenu = input('post.quickmenu');
            $quickmenu = str_replace(chr(10),'',$quickmenu);
            $menu_arr = explode(chr(13),$quickmenu);
            $res = mac_arr2file(APP_PATH . 'extra/quickmenu.php', $menu_arr);
            if ($res === false) {
                return $this->error(lang('save_err'));
            }
            return $this->success(lang('save_ok'));
        }
        else{
            $config_menu = config('quickmenu');
            if(empty($config_menu)){
                $quickmenu = mac_read_file(APP_PATH.'data/config/quickmenu.txt');
            }
            else{
                $quickmenu = array_values($config_menu);
                $quickmenu = join(chr(13),$quickmenu);
            }
            $this->assign('quickmenu',$quickmenu);
            $this->assign('title',lang('admin/index/quickmenu/title'));
            return $this->fetch('admin@index/quickmenu');
        }
    }

    public function checkcache()
    {
        $res = 'no';
        $r = cache('cache_data');
        if($r=='1'){
            $res = 'haved';
        }
        echo $res;
    }

    public function clear()
    {
        $res = $this->_cache_clear();
        //运行缓存
        if(!$res) {
            $this->error(lang('admin/index/clear_err'));
        }
        // 搜索缓存结果清理
        model('VodSearch')->clearOldResult(true);
        return $this->success(lang('admin/index/clear_ok'));
    }

    public function iframe()
    {
        $val = input('post.val', 0);
        if ($val != 0 && $val != 1) {
            return $this->error(lang('admin/index/clear_ok'));
        }
        if ($val == 1) {
            cookie('is_iframe', 'yes');
        } else {
            cookie('is_iframe', null);
        }
        return $this->success(lang('admin/index/iframe'));
    }

    public function unlocked()
    {
        $param = input();
        $password = $param['password'];

        if($this->_admin['admin_pwd'] != md5($password)){
            return $this->error(lang('admin/index/pass_err'));
        }

        return $this->success(lang('admin/index/unlock_ok'));
    }

    public function check_back_link()
    {
        $param = input();
        $res = mac_check_back_link($param['url']);
        return json($res);
    }

    public function select()
    {
        $param = input();
        $tpl = $param['tpl'];
        $tab = $param['tab'];
        $col = $param['col'];
        $ids = $param['ids'];
        $url = $param['url'];
        $val = $param['val'];

        $refresh = $param['refresh'];

        if(empty($tpl) || empty($tab) || empty($col) || empty($ids) || empty($url)){
            return $this->error(lang('param_err'));
        }

        if(is_array($ids)){
            $ids = join(',',$ids);
        }

        if(empty($refresh)){
            $refresh = 'yes';
        }

        $url = url($url);
        $mid = 1;
        if($tab=='art'){
            $mid = 2;
        }
        elseif($tab=='actor'){
            $mid=8;
        }
        elseif($tab=='website'){
            $mid=11;
        }
        $this->assign('mid',$mid);

        if($tpl=='select_type'){
            $type_tree = model('Type')->getCache('type_tree');
            $this->assign('type_tree',$type_tree);
        }
        elseif($tpl =='select_level'){
            $level_list = [1,2,3,4,5,6,7,8,9];
            $this->assign('level_list',$level_list);
        }

        $this->assign('refresh',$refresh);
        $this->assign('url',$url);
        $this->assign('tab',$tab);
        $this->assign('col',$col);
        $this->assign('ids',$ids);
        $this->assign('val',$val);
        return $this->fetch( 'admin@public/'.$tpl);
    }

    public function get_system_status(){
        //判斷系統
        $os_name = strtoupper(substr(PHP_OS,0,3));
        $os_data = [];
        $os_data['os_name'] = '';
        if ($os_name == 'WIN'){
            $os_data['os_name'] = 'WINDOWS';
            $os_data['disk_datas'] = $this->get_spec_disk('all');
            $os_data['cpu_usage'] = $this->getCpuUsage();
            $mem_arr = $this->getMemoryUsage();
            $os_data['mem_usage'] = $mem_arr['usage'];
            $os_data['mem_total'] = round($mem_arr['TotalVisibleMemorySize']/1024,2);
            $os_data['mem_used'] = $os_data['mem_total'] - round($mem_arr['FreePhysicalMemory']/1024,2);
        }
        else if($os_name == 'LIN'){
            $os_data['os_name'] = 'LINUX';
            $totalSpace = disk_total_space('/');    // 获取根目录的总容量
            $freeSpace = disk_free_space('/');      // 获取根目录的可用容量
            $totalSpaceGB = $totalSpace / (1024 * 1024 * 1024);   // 将总容量转换为GB
            $freeSpaceGB = $freeSpace / (1024 * 1024 * 1024);     // 将可用容量转换为GB
            $tmp_disk_data = [];
            $tmp_disk_data[0] = $totalSpaceGB-$freeSpaceGB;
            $tmp_disk_data[1] = $totalSpaceGB;
            $tmp_disk_data[2] = (100-round(($freeSpaceGB/$totalSpaceGB)*100, 2));
            $os_data['disk_datas']['/'] = $tmp_disk_data;
            $mem_arr = $this->get_linux_server_memory_usage();
            $os_data['mem_usage'] = $mem_arr['usage'];
            $os_data['mem_used'] = $mem_arr['used'];
            $os_data['mem_total'] = $mem_arr['total'];
            $os_data['cpu_usage'] = '';
            if (is_readable("/proc/stat"))
            {
                $statData1 = $this->_getServerLoadLinuxData();
                sleep(0.5);
                $statData2 = $this->_getServerLoadLinuxData();
                if
                (
                    (!is_null($statData1)) &&
                    (!is_null($statData2))
                )
                {
                    $statData2[0] -= $statData1[0];
                    $statData2[1] -= $statData1[1];
                    $statData2[2] -= $statData1[2];
                    $statData2[3] -= $statData1[3];
    
                    $cpuTime = $statData2[0] + $statData2[1] + $statData2[2] + $statData2[3];
    
                    $os_data['cpu_usage']  = 100 - ($statData2[3] * 100 / $cpuTime);
                }
            }
        }
 
        return $os_data;
    }
    private function byte_format($size,$dec=2)
    {
        $a = array("B", "KB", "MB", "GB", "TB", "PB","EB","ZB","YB");
        $pos = 0;
        while ($size >= 1024)   
        {
            $size /= 1024;
            $pos++;
        }
        return round($size,$dec);
    }
    private function get_disk_space($letter)
    {
        //获取磁盘信息
        $diskct = 0;
        $disk = array();

        $diskz = 0; //磁盘总容量
        $diskk = 0; //磁盘剩余容量
        $is_disk = $letter.':';
        if(@disk_total_space($is_disk)!=NULL)
        {
        $diskct++;
        $disk[$letter][0] = $this->byte_format(@disk_free_space($is_disk));
        $disk[$letter][1] = $this->byte_format(@disk_total_space($is_disk));
        // $disk[$letter][2] = (100-round(((@disk_free_space($is_disk)/(1024*1024*1024))/(@disk_total_space($is_disk)/(1024*1024*1024)))*100,2));
        $disk[$letter][2] = (round(100-round(((@disk_free_space($is_disk)/(1024*1024*1024))/(@disk_total_space($is_disk)/(1024*1024*1024)))*100,2),2));
        $diskk+=$this->byte_format(@disk_free_space($is_disk));
        $diskz+=$this->byte_format(@disk_total_space($is_disk));
        }
        return $disk; 
    }
    private function get_spec_disk($type='system')
    {
        $disk = array();
        switch ($type)
        {
        case 'system':
            //strrev(array_pop(explode(':',strrev(getenv_info('SystemRoot')))));//取得系统盘符
            $disk = $this->get_disk_space(strrev(array_pop(explode(':',strrev(getenv('SystemRoot'))))));
            break;
        case 'all':
            foreach (range('b','z') as $letter)
            {
            $disk = array_merge($disk,$this->get_disk_space(strtoupper($letter)));
            }
            break;
        default:
            $disk = $this->get_disk_space($type);
            break;
        }
        return $disk;
    }

    private function getFilePath($fileName, $content)
    {
        $path = dirname(__FILE__) . "\\$fileName";
        if (!file_exists($path)) {
            file_put_contents($path, $content);
        }
        return $path;
    }
 
    /**
     * 获得cpu使用率vbs文件生成函数
     * @return string 返回vbs文件路径
     */
    private function getCupUsageVbsPath()
    {
        return $this->getFilePath(
            'cpu_usage.vbs',
            "On Error Resume Next
    Set objProc = GetObject(\"winmgmts:\\\\.\\root\cimv2:win32_processor='cpu0'\")
    WScript.Echo(objProc.LoadPercentage)"
        );
    }
 
    /**
     * 获得总内存及可用物理内存JSON vbs文件生成函数
     * @return string 返回vbs文件路径
     */
    private function getMemoryUsageVbsPath()
    {
        return $this->getFilePath(
            'memory_usage.vbs',
            "On Error Resume Next
    Set objWMI = GetObject(\"winmgmts:\\\\.\\root\cimv2\")
    Set colOS = objWMI.InstancesOf(\"Win32_OperatingSystem\")
    For Each objOS in colOS
     Wscript.Echo(\"{\"\"TotalVisibleMemorySize\"\":\" & objOS.TotalVisibleMemorySize & \",\"\"FreePhysicalMemory\"\":\" & objOS.FreePhysicalMemory & \"}\")
    Next"
        );
    }
 
    /**
     * 获得CPU使用率
     * @return Number
     */
    private function getCpuUsage()
    {
        $path = $this->getCupUsageVbsPath();
        exec("cscript -nologo $path", $usage);
        return $usage[0];
    }
 
    /**
     * 获得内存使用率数组
     * @return array
     */
    private function getMemoryUsage()
    {
        $path = $this->getMemoryUsageVbsPath();
        exec("cscript -nologo $path", $usage);
        $memory = json_decode($usage[0], true);
        $memory['usage'] = Round((($memory['TotalVisibleMemorySize'] - $memory['FreePhysicalMemory']) / $memory['TotalVisibleMemorySize']) * 100);
        return $memory;
    }

    private function get_linux_server_memory_usage(){
        $free = shell_exec('free');
        $free = (string)trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);
        $memory_usage = $mem[2]/$mem[1]*100;
        $mem_array = [];
        $mem_array['total'] = round($mem[1]/1024,2);
        $mem_array['used'] = round($mem[2]/1024,2);
        $mem_array['usage'] = round($memory_usage,2);
        return $mem_array;
    }

    private function _getServerLoadLinuxData()
    {
        if (is_readable("/proc/stat"))
        {
            $stats = @file_get_contents("/proc/stat");
    
            if ($stats !== false)
            {
                // Remove double spaces to make it easier to extract values with explode()
                $stats = preg_replace("/[[:blank:]]+/", " ", $stats);
    
                // Separate lines
                $stats = str_replace(array("\r\n", "\n\r", "\r"), "\n", $stats);
                $stats = explode("\n", $stats);
    
                // Separate values and find line for main CPU load
                foreach ($stats as $statLine)
                {
                    $statLineData = explode(" ", trim($statLine));
    
                    // Found!
                    if
                    (
                        (count($statLineData) >= 5) &&
                        ($statLineData[0] == "cpu")
                    )
                    {
                        return array(
                            $statLineData[1],
                            $statLineData[2],
                            $statLineData[3],
                            $statLineData[4],
                        );
                    }
                }
            }
        }
    
        return null;
    }

    private function getAdminDashboardData(){
        $result = [];
        //已注册总用户数量
        $result['user_count'] = model('User')->count();
        $result['user_count'] = number_format($result['user_count'],0,'.',',');
        //已审核用户数量
        $result['user_active_count'] = model('User')->where('user_status',1)->count();
        $result['user_active_count'] = number_format($result['user_active_count'],0,'.',',');

        $today_start = strtotime(date('Y-m-d 00:00:00'));
        $today_end = $today_start+86399;
        //本日来客量
        $result['today_visit_count'] = model('Visit')->where('visit_time','between',$today_start.','.$today_end)->count();
        $result['today_visit_count'] = number_format($result['today_visit_count'],0,'.',',');
        //本日总入金
        $result['today_money_get']  = model('Order')->where('order_time','between',$today_start.','.$today_end)->where('order_status',1)->sum('order_price');
        $result['today_money_get'] = number_format($result['today_money_get'],2,'.',',');
        //前七天 每日用户访问数
        $tmp_arr = Db::query("select FROM_UNIXTIME(visit_time, '%Y-%c-%d' ) days,count(*) count from (SELECT * from mac_visit where visit_time >= (unix_timestamp(CURDATE())-604800)) as temp group by days");
        $result['seven_day_visit_day'] = [];
        $result['seven_day_visit_count'] = [];

        $result['raise_visit_user_today'] = 0;
        if (is_array($tmp_arr) && count($tmp_arr)>1 && (strtotime(end($tmp_arr)['days'])==strtotime(date('Y-m-d')) ) ){
            $yesterday_visit_count = $tmp_arr[count($tmp_arr)-2]['count'];
            $lastday_visit_count = end($tmp_arr)['count'];
            $result['raise_visit_user_today'] =  number_format((($lastday_visit_count-$yesterday_visit_count)/$yesterday_visit_count)*100,2,'.',',');
        }

        foreach( $tmp_arr as $data){
            array_push($result['seven_day_visit_day'],$data['days']);
            array_push($result['seven_day_visit_count'],$data['count']);
        }
        
        //近七日用户访问总量
        $result['seven_day_visit_total_count'] = 0;
        foreach ( $result['seven_day_visit_data'] as $k=>$value ){
            $result['seven_day_visit_total_count'] = $result['seven_day_visit_total_count'] + $value['count'];
        }
        $result['seven_day_visit_total_count'] = number_format($result['seven_day_visit_total_count'],0,'.',',');
        //前七天 每日用户注册数
        $result['seven_day_reg_data'] =  Db::query("select FROM_UNIXTIME(user_reg_time, '%Y-%c-%d' ) days,count(*) count from (SELECT * from mac_user where user_reg_time >= (unix_timestamp(CURDATE())-604800)) as tmp group by days");
        //近七日用户注册总量
        $result['seven_day_reg_total_count'] = 0;
        foreach ( $result['seven_day_reg_data'] as $k=>$value ){
            $result['seven_day_reg_total_count'] = $result['seven_day_reg_total_count'] + $value['count'];
        }
    
        //比較前一天的註冊量漲幅
        $result['raise_reg_user_today'] = 0;
        if (is_array($result['seven_day_reg_data']) && count($result['seven_day_reg_data'])>1 && (strtotime(end($result['seven_day_reg_data'])['days'])==strtotime(date('Y-m-d')) ) ){
            $yesterday_reg_count = $result['seven_day_reg_data'][count($result['seven_day_reg_data'])-2]['count'];
            $lastday_reg_count = end($result['seven_day_reg_data'])['count'];
            $result['raise_reg_user_today'] =  number_format((($lastday_reg_count-$yesterday_reg_count)/$yesterday_reg_count)*100,2,'.',',');
        }

        $result['seven_day_reg_total_count'] = number_format($result['seven_day_reg_total_count'],0,'.',',');
        return $result;
    }
    public function rangeDateDailyVisit(){
        
        $range_daily_visit_data = Db::query("select FROM_UNIXTIME(visit_time, '%Y-%c-%d' ) days,count(*) count from (SELECT * from mac_visit where visit_time >= ".strtotime($_POST['startDate'])."&&  visit_time <= ".strtotime($_POST['endDate'])." ) as temp group by days");
        $result = [];
        $range_visit_day = [];
        $range_visit_count = [];
        $range_visit_sum = 0;
        foreach( $range_daily_visit_data as $data ){
            $range_visit_sum = $range_visit_sum + $data['count'];
            array_push($range_visit_day,$data['days']);
            array_push($range_visit_count,$data['count']);
        }

        $result['days'] = $range_visit_day;
        $result['count'] = $range_visit_count;
        $result['sum'] = $range_visit_sum;
        return json_encode($result);
    }

    public function botlist(){
        $day_arr =[];
        //列出最近10天的日期
        for($i=0;$i<7;$i++){
            $day_arr[$i] = date('Y-m-d',time()-$i*60*60*24);
        }
        $google_arr=[];
        $baidu_arr=[];
        $sogou_arr=[];
        $soso_arr=[];
        $yahoo_arr=[];
        $msn_arr=[];
        $msn_bot_arr=[];
        $sohu_arr=[];
        $yodao_arr=[];
        $twiceler_arr=[];
        $alexa_arr=[];
        $bot_list=[];
        foreach ($day_arr as $day_vo){
            if (file_exists(ROOT_PATH . 'runtime/log/bot/'.$day_vo.'.txt')){
                $bot_content = file_get_contents(ROOT_PATH . 'runtime/log/bot/'.$day_vo.'.txt');
            }else{
                $bot_content = '';
            }
            $google_arr[$day_vo]=substr_count($bot_content,'Google');
            $baidu_arr[$day_vo]=substr_count($bot_content,'Baidu');
            $sogou_arr[$day_vo]=substr_count($bot_content,'Sogou');
            $soso_arr[$day_vo]=substr_count($bot_content,'SOSO');
            $yahoo_arr[$day_vo]=substr_count($bot_content,'Yahoo');
            $msn_arr[$day_vo]=substr_count($bot_content,'MSN');
            $msn_bot_arr[$day_vo]=substr_count($bot_content,'msnbot');
            $sohu_arr[$day_vo]=substr_count($bot_content,'Sohu');
            $yodao_arr[$day_vo]=substr_count($bot_content,'Yodao');
            $twiceler_arr[$day_vo]=substr_count($bot_content,'Twiceler');
            $alexa_arr[$day_vo]=substr_count($bot_content,'Alexa');
        }
        $bot_list['Google']['key']=array_keys($google_arr);
        $bot_list['Google']['values']=array_values($google_arr);
        $bot_list['Baidu']['keys']=array_keys($baidu_arr);
        $bot_list['Baidu']['values']=array_values($baidu_arr);
        $bot_list['Sogou']['keys']=array_keys($sogou_arr);
        $bot_list['Sogou']['values']=array_values($sogou_arr);
        $bot_list['SOSO']['keys']=array_keys($soso_arr);
        $bot_list['SOSO']['values']=array_values($soso_arr);
        $bot_list['Yahoo']['keys']=array_keys($yahoo_arr);
        $bot_list['Yahoo']['values']=array_values($yahoo_arr);
        $bot_list['MSN']['keys']=array_keys($msn_arr);
        $bot_list['MSN']['values']=array_values($msn_arr);
        $bot_list['msnbot']['keys']=array_keys($msn_bot_arr);
        $bot_list['msnbot']['values']=array_values($msn_bot_arr);
        $bot_list['Sohu']['keys']=array_keys($sohu_arr);
        $bot_list['Sohu']['values']=array_values($sohu_arr);
        $bot_list['Yodao']['keys']=array_keys($yodao_arr);
        $bot_list['Yodao']['values']=array_values($yodao_arr);
        $bot_list['Twiceler']['keys']=array_keys($twiceler_arr);
        $bot_list['Twiceler']['values']=array_values($twiceler_arr);
        $bot_list['Alexa']['keys']=array_keys($alexa_arr);
        $bot_list['Alexa']['values']=array_values($alexa_arr);

        if(!empty($_POST['category'])){
            return $bot_list[$_POST['category']];
        }
        else{
            return $bot_list;
        }
    }

    public function botlog(){
        $parm = input();
        $data = $parm['data'];
        $bot_content = file_get_contents(ROOT_PATH . 'runtime/log/bot/'.$data.'.txt');
        $bot_list = array_slice(array_reverse(explode("\r\n",trim($bot_content))),0,20);
        $this->assign('bot_list',$bot_list);
        return $this->fetch('admin@others/botlog');
    }

}
