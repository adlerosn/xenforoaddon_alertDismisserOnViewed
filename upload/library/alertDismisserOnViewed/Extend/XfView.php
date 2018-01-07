<?php

class alertDismisserOnViewed_Extend_XfView extends XFCP_alertDismisserOnViewed_Extend_XfView {
	public function prepareParams(){
		$visitor = XenForo_Visitor::getInstance();
		if($visitor['user_id']){
			$links = [];
			$alertModel = XenForo_Model::create('XenForo_Model_Alert');
			//getting all alerts
			$alerts = $alertModel->getAlertsForUser(
				$visitor['user_id'],
				XenForo_Model_Alert::FETCH_MODE_ALL
			);
			//removing viewed alerts from list
			foreach ($alerts['alerts'] AS $alertId => $alert)
				if(!$alert['unviewed'] || $alert['alerted_user_id']!=$visitor['user_id'])
					unset($alerts['alerts'][$alertId]);
			//putting templates on alerts
			$templates = XenForo_ViewPublic_Helper_Alert::getTemplates(
				$this,
				$alerts['alerts'],
				$alerts['alertHandlers']
			);
			//getting links from rendered template
			foreach($templates as $alertId=>&$alert){
				if(array_key_exists('template',$alert)){
					$alert['rendered']=$alert['template']->render();
					if(preg_match('#</a>[^<]*<a[^>]*href="([^"]*)"[^>]*>#',$alert['rendered'],$matches)>=1){
						$links[$alertId][]=$matches[1];
					}
				}
			}
			//getting links for Thread and Post cases
			foreach($templates as $alertId=>$alert){
				if($alert['content_type']=='post'){
					$postModel = XenForo_Model::create('XenForo_Model_Post');
					$threadModel = XenForo_Model::create('XenForo_Model_Thread');
					$post = $postModel->getPostById($alert['content_id']);
					if(!$post)
						continue;
					$links[$alertId][] = XenForo_Link::buildPublicLink('posts',$post);
					$thread = $threadModel->getThreadById($post['thread_id']);
					if(!$thread)
						continue;
					$links[$alertId][] = XenForo_Link::buildPublicLink('threads',$thread);
					continue;
				}
				else 
				if($alert['content_type']=='thread'){
					$threadModel = XenForo_Model::create('XenForo_Model_Thread');
					$thread = $threadModel->getThreadById($alert['content_id']);
					if(!$thread)
						continue;
					$links[$alertId][] = XenForo_Link::buildPublicLink('threads',$thread);
					continue;
				}
			}
			//discovering what page is being displayed
			$currentUri='';
			if($this->_renderer!=null && $this->_renderer->getRequest()!=null && $this->_renderer->getRequest()->getRequestUri() != null)
				$currentUri = $this->_renderer->getRequest()->getRequestUri();
			//marking alerts that are being seen in the current page
			$alertsToDismiss = [];
			foreach($links as $alertId=>$urls){
				foreach($urls as $url){
					if(strpos($currentUri,$url)){
						$alertsToDismiss[]=$alertId;
					}
				}
			}
			$alertsToDismiss = array_unique($alertsToDismiss);
			//dismissing alerts
			$dismissed = 0;
			foreach($alertsToDismiss as $alertId){
				$alertDw = XenForo_DataWriter::create('XenForo_DataWriter_Alert');
				$alertDw->setExistingData($alertId);
				$alertDw->set('view_date',time());
				$alertDw->save();
				unset($alerts[$alertId]);
				$dismissed++;
			}
			//updating user
			$previous = $visitor['alerts_unread'];
			$current  = $previous-$dismissed;
			if($current!=$previous){
				$userDw = XenForo_DataWriter::create('XenForo_DataWriter_User');
				$userDw->setExistingData($visitor['user_id']);
				$userDw->set('alerts_unread',$current);
				$userDw->save();
			}
			unset($alerts['alertHandlers']);
			//die(print_r([$previous,$current,$alertsToDismiss,$links,$currentUri,$alerts],true));
		}
		return parent::prepareParams();
	}
}
