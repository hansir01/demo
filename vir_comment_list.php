<?php
/**
 * 虚拟评论列表页
 *
 * @author:hanshaobo
 * @date:2014-01-17
 *
 * */
class Vir_comment_list extends MY_Controller
{
	function __construct()
	{
		parent::__construct();
		$this->load->model('ecmall/virtual_order_mdl','virOrder');
		$this->load->model('ecmall/virtual_user_mdl','virUser');
		$this->load->model('ecmall/virtual_user_phase_mdl','virUserPh');
		$this->load->model('ecmall/comment_tag_mdl','comTag');
		$this->load->library('page'); 
	}
	/*
	 *显示虚拟评论数据 
	 */ 
	function index()
	{
		//变量初始化
		$data = $where= $whTag = $whPhase = $whereLike =$whereIn= array();
		$data['check'] = $data['phase_id'] = $data['tag_id'] = $data['nodata'] =$data['da'] = 0 ;
		$data['userName'] = $data['goodsName'] = ''; 
		$param = '?sub=sub';
		$nowdate = date("Y-m-d H:i:s");
		//获取用户的权限
		$data['admin'] = $this->adminFlag;
	   //获取用户名	
		if(isset($_GET['userName']) && !empty($_GET['userName']))
		{
			$data['da'] = 1;
			$data['userName'] = $whereLike['virtual_user_name'] =addslashes($_GET['userName']);
			$param .= '&userName='.$whereLike['virtual_user_name'];
		}
		//获取商户名称
		if(isset($_GET['goodsName']) && !empty($_GET['goodsName']))
		{
			$data['da'] = 1;
			$data['goodsName'] = $whereLike['seller_name'] =addslashes($_GET['goodsName']);
			$param .= '&goodsName='.$whereLike['seller_name'];
		}
		//获取用户阶段
		if(isset($_GET['phase']) && $_GET['phase'] !=0)
		{
			$data['da'] = 1;
			$data['phase_id'] = $whPhase['phase_id'] = intval($_GET['phase']);
			$param .='&phase='.$whPhase['phase_id']; 
			$whdata= $this->virUser->getTotal($whPhase,$limit=0, $offset=0, $order='',$where_in=array(),$where_like=array(),$field="virtual_user_id",$where_not_in=array());
			if(!empty($whdata))
			{
				$whereIn = $this->_implode_arr($whdata,'virtual_user_id');
			}else
			{
				$whereIn = array('field'=>'virtual_user_id','values'=>0);
			}
		}
		//获取评论标签
		if(isset($_GET['tag']) && $_GET['tag'] !=0)
		{
			$data['da'] = 1;
			$data['tag_id'] = $where['comment_tag_id'] = intval($_GET['tag']);
			$param .='&tag='.$where['comment_tag_id'];
		}
		//获取审核状态
		if(isset($_GET['check']) && intval($_GET['check']) != 0)
		{
			if(intval($_GET['check'] != -1))
			{
				$data['da'] = 1;
				$data['check'] = $where['check'] = intval($_GET['check']);
				$param .='&check='.$data['check'];
			}else{
				$data['check'] = -1;
				$param .='&check=-1';

			}
		}else
		{
			$data['da'] = 1;
			$where['check'] = 0;
			$param .='&check=0';

		}
		$config['per_page'] = '10';//每页显示的个数
		$cur_page = $this->input->get('per_page');//传过来的的页数                                                                            
		$cp = !empty($cur_page) ? $cur_page : 0;
		$param .= '&cp='.$cp;
		$data['cp'] = $cp;
		$limit= (!empty($cur_page)) ? $config['per_page'] : $config['per_page'];
		$data['per_page'] = $cur_page;
		//获取评论标签表中所有数据
		$data['tag'] = $this->comTag->getTotal();
		//获取用户阶段表中所有数据
		$data['phase']= $this->virUserPh->getTotal();
		//用户订单表根据条件获取的数据
		$total = $this->virOrder->getTotal($where,$limit, $cp, $order='comment_add_time desc',$whereIn,$whereLike,$field="*",$where_not_in=array());
		//用户订单表总数
		$data['num'] =  $this->virOrder->getTotalNum($where,$whereIn,$whereLike);
		if(!empty($total) && $data['num'] > 0)
		{
			foreach($total as $key => $val)
			{
				//获取当前评论标签名
				$total[$key]['time'] = 0;
				$tag_name = $this->comTag->getOne(intval($val['comment_tag_id']));
				//获取当前用户状态名
				$phase_id = $this->virUser->getOne(intval($val['virtual_user_id']));
				$phase_name = $this->virUserPh->getOne(intval($phase_id['phase_id']));
				$total[$key]['phase_name'] = $phase_name['phase_name'];
				$total[$key]['tag_name'] = $tag_name['tag_name'];
				if($val['comment_add_time'] == '0000-00-00 00:00:00')
				{
					$total[$key]['comment_add_time'] = '-----';
				}
				//判断审核状态
				switch($val['check'])
				{
					case 0 : $total[$key]['check_name'] = '未审';
							 //判断是否出现审核按钮
							 $onedate = date("Y-m-d H:i:s",strtotime($val['add_time'])+86400);
							 $total[$key]['time'] = $nowdate > $onedate ? 1 : 0;
							 break;
					case 1 : $total[$key]['check_name'] = '通过';break;
					case 2 : $total[$key]['check_name'] = '驳回';break;
				}
				unset($tag_name);
				unset($phase_name);
			}
		}else
		{
			$data['nodata'] = 1;
		}
		$data['total'] =  $total;
		$config['base_url'] = "/index.php/virtual_data/vir_comment_list".$param;                                     
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
		$this->load->view('virtual_data/vir_comment_list.html',$data);
	}
	/*
	 *列表页的删除功能
	 */
	function del()
	{
		//删除失败后跳转页面
		$url = '/index.php/virtual_data/vir_comment_list';
		$message['state']=3;
		//获取删除评论的id
		$virorderid = isset($_GET['virorderid']) && $_GET['virorderid'] !=0 ? intval($_GET['virorderid']) : '';
		if(!empty($virorderid))
		{
			//获取用户id
			$vir_user = $this->virOrder->getOne($virorderid,'virtual_user_id');
			if(!empty($vir_user))
			{
				//获取用户的购买数量
				$vir_count = $this->virUser->getOne(intval($vir_user['virtual_user_id']),'virtual_user_id,virtual_order_count');
				if($vir_count['virtual_order_count'] > 0)
				{
					//删除时,购买数量减1
					$data['virtual_order_count'] = intval($vir_count['virtual_order_count']) - 1;
					//修改用户购买数量
					$ok = $this->virUser->update(intval($vir_count['virtual_user_id']),$data);
					if($ok)
					{
						//删除数据
						$del = $this->virOrder->del($virorderid);
						if($del)
						{
							header('Location: /index.php/virtual_data/vir_comment_list');
						}else
						{
							//如果删除失败,还原购买数量
							$data['virtual_order_count'] = intval($vir_count['virtual_order_count']);
							$this->virUser->update(intval($vir_count['virtual_user_id']),$data);
							$message['content'] = '删除失败,请找技术支持!';
							page_message($message,$url);
						}
					}else
					{
						$message['content'] = '该用户购买数量修改错误!';
						page_message($message,$url);
					}
				}else
				{
					$message['content'] = '该用户的购买数量已为0!';
					page_message($message,$url);
				}
			}else
			{
				$message['content'] = '无该用户数据!';
				page_message($message,$url);
			}
		}
	}
	/*
	 *将二维数组转化为一维数组
	 *
	 * @param $arr 为二维数组	$arr=array(0=>array('field'=>1),1=>array('field'=>2))
	 * @param $field  $field为$arr中的field字段 
	 *
	 * */

	function _implode_arr($arr,$field)
	{
		$data = $narr = array();
		foreach($arr as $key => $val)
		{
			$narr[$key] = $val[$field]; 
		}
		$data['field'] = $field;
		$data['values'] = $narr;
		return $data;
	}
}
?>
