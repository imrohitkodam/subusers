#
#<?php die('Forbidden.'); ?>
#Date: 2026-02-20 04:30:47 UTC
#Software: Joomla! 6.0.0 Stable [ Kuimarisha ] 14-October-2025 16:00 UTC

#Fields: datetime	priority clientip	category	message
2026-02-20T04:30:47+00:00	CRITICAL 127.0.0.1	error	Uncaught Throwable of type Exception thrown with message "View class not found [class, file]: joomlacmsmvccontrollerbaseViewusers, [ROOT]/administrator/components/com_subusers/views/users/view.html.php". Stack trace: #0 [ROOT]/libraries/src/MVC/Controller/BaseController.php(609): Joomla\CMS\MVC\Factory\LegacyFactory->createView()
#1 [ROOT]/libraries/src/MVC/Controller/BaseController.php(873): Joomla\CMS\MVC\Controller\BaseController->createView()
#2 [ROOT]/libraries/src/MVC/Controller/BaseController.php(655): Joomla\CMS\MVC\Controller\BaseController->getView()
#3 [ROOT]/libraries/src/MVC/Controller/BaseController.php(730): Joomla\CMS\MVC\Controller\BaseController->display()
#4 [ROOT]/administrator/components/com_subusers/subusers.php(49): Joomla\CMS\MVC\Controller\BaseController->execute()
#5 [ROOT]/libraries/src/Dispatcher/LegacyComponentDispatcher.php(71): require_once('...')
#6 [ROOT]/libraries/src/Dispatcher/LegacyComponentDispatcher.php(73): Joomla\CMS\Dispatcher\LegacyComponentDispatcher::Joomla\CMS\Dispatcher\{closure}()
#7 [ROOT]/libraries/src/Component/ComponentHelper.php(361): Joomla\CMS\Dispatcher\LegacyComponentDispatcher->dispatch()
#8 [ROOT]/libraries/src/Application/AdministratorApplication.php(150): Joomla\CMS\Component\ComponentHelper::renderComponent()
#9 [ROOT]/libraries/src/Application/AdministratorApplication.php(205): Joomla\CMS\Application\AdministratorApplication->dispatch()
#10 [ROOT]/libraries/src/Application/CMSApplication.php(320): Joomla\CMS\Application\AdministratorApplication->doExecute()
#11 [ROOT]/administrator/includes/app.php(58): Joomla\CMS\Application\CMSApplication->execute()
#12 [ROOT]/administrator/index.php(32): require_once('...')
#13 {main}
