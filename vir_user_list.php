<?php
/**
 *虚拟用户列表页
 *
 *@author:hanshaobo
 *@date:2014-01-20
 *
 * */
class Vir_user_list extends CI_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('ecmall/virtual_user_mdl','virUser');
		$this->load->model('ecmall/virtual_user_phase_mdl','virUserPh');
		$this->load->library('page'); 
	}
	/*
	 *显示虚拟用户数据
	 */
	function index()
	{
		//初始化变量
		$data = $where = $whPhase = $whereLike = array();
		$data['userName'] = '';
		$data['nodata'] = $data['phase_id'] = $data['da'] = 0;
		$param = '?sub=sub';
		//获取用户名	
		if(isset($_GET['userName']) && !empty($_GET['userName']))
		{
			$data['da'] = 1;
			$data['userName'] = $whereLike['user_name'] =addslashes($_GET['userName']);
			$param .= '&userName='.$whereLike['user_name'];
		}
		//获取用户阶段
		if(isset($_GET['phase']) && $_GET['phase'] !=0)
		{
			$data['da'] = 1;
			$data['phase_id'] = $whPhase['phase_id'] = intval($_GET['phase']);
			$param .='&phase='.$whPhase['phase_id']; 
		}
		$config['per_page'] = '10';//每页显示的个数
		$cur_page = $this->input->get('per_page');//传过来的的页数                                                                            
		$cp = !empty($cur_page) ? $cur_page : 0;
		$param .= '&cp='.$cp;
		$data['cp'] = $cp;
		$limit= (!empty($cur_page)) ? $config['per_page'] : $config['per_page'];
		$data['per_page'] = $cur_page;
		//获取用户阶段
		$data['phase'] = $this->virUserPh->getTotal();
		//获取所有用户
		$total = $this->virUser->getTotal($whPhase,$limit, $cp, $order='virtual_user_id asc',$where_in=array(),$whereLike,$field="*",$where_not_in=array());
		//获取总数	
		$data['num'] = $this->virUser->getTotalNum($whPhase,$where_in=array(),$whereLike);
		//处理数组
		if(!empty($total) && $data['num'] > 0)
		{
			foreach($total as $key => $val)
			{
				if($val['user_add_time'] == '0000-00-00 00:00:00')
				{
					$total[$key]['user_add_time'] = '-----';
				}
				$phase_data = $this->virUserPh->getOne(intval($val['phase_id']));
				$total[$key]['phase_name'] = $phase_data['phase_name'];
			}
		
		}else
		{
			$data['nodata'] = 1;
		}
		$data['total'] = $total;
		$config['base_url'] = "/index.php/virtual_data/vir_user_list".$param;                                     
		$config['total_rows'] = $data['num'];
		$config['next_link'] = '<span style="margin:0 5px;"><img src="/static/images/button_16.gif" /></span>'; // 下一页显示
		$config['prev_link'] = '<span style="margin:0 5px;"><img src="/static/images/button_14.gif" /></span>'; // 上一页显示
		$config['cur_tag_open'] = ' <span class="paging_ahover">'; // 当前页开始样式
		$config['cur_tag_close'] = '</span>'; // 当前页结束样式
		$config['last_link'] = '<span class="paging_last">尾页</span>';
		$config['first_link'] = '<span class="paging_home">首页</span>';
		$config['num_tag_open'] = '<span class="paging_initial">';//每一页样式标签开始
		$config['num_tag_close'] = '</span>';//每一页样式标签结束
		$this->page->initialize($config);
		$data['pageinfo'] = $this->page->create_links();
		$data['pageinfo'] = str_replace('&nbsp;','', $data['pageinfo']);
		$this->load->view('virtual_data/vir_user_list.html',$data);
	}
}
?>
