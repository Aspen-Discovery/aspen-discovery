<?php


class WebBuilder_SubmitForm extends Action
{
	function launch()
	{
		require_once ROOT_DIR . '/sys/WebBuilder/CustomForm.php';
		$id = strip_tags($_REQUEST['id']);
		$form = new CustomForm();
		$form->id = $id;
		if (!$form->find(true)){
			$this->display('../Record/invalidPage.tpl', 'Invalid Page');
			die();
		}
		global $interface;
		if (isset($_REQUEST['submit'])) {
			//Get the form values
			//Convert the form values to JSON
			//Save the form values to the database

			$interface->assign('submissionResultText', $form->submissionResultText);
		}else{
			$interface->assign('submissionError', 'The form was not submitted correctly');
		}

		$this->display('customFormResults.tpl', $form->title, 'Search/home-sidebar.tpl', false);
	}
}