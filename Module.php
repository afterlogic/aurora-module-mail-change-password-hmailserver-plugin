<?php
/**
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or AfterLogic Software License
 *
 * This code is licensed under AGPLv3 license or AfterLogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\MailChangePasswordHmailserverPlugin;

/**
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractModule
{
	/**
	 * @var
	 */
	protected $oBaseApp;

	/**
	 * @var
	 */
	protected $oAdminAccount;

	/**
	 * @param CApiPluginManager $oPluginManager
	 */
	
	public function init() 
	{
		$this->oBaseApp = null;
		$this->oAdminAccount = null;
		
		$this->oMailModule = \Aurora\System\Api::GetModule('Mail');
	
		$this->subscribeEvent('Mail::ChangePassword::before', array($this, 'onBeforeChangePassword'));
	}
	
	protected function initializeServer()
	{
		if (null === $this->oBaseApp)
		{
			if (class_exists('COM'))
			{
				$this->oBaseApp = new \COM("hMailServer.Application");
				try
				{
					$this->oBaseApp->Connect();
					$this->oAdminAccount = $this->oBaseApp->Authenticate(
						$this->getConfig('AdminUser', 'Administrator'),
						$this->getConfig('AdminPass', '')
					);
				}
				catch(Exception $oException)
				{
					\Aurora\System\Api::Log('Initialize Server Error');
					\Aurora\System\Api::LogObject($oException);
				}
			}
			else 
			{
				\Aurora\System\Api::Log('Unable to load class: COM');
			}
		}		
	}	
	
	/**
	 * 
	 * @param array $aArguments
	 * @param mixed $mResult
	 */
	public function onBeforeChangePassword($aArguments, &$mResult)
	{
		$mResult = true;
		
		$oAccount = $this->oMailModule->GetAccount($aArguments['AccountId']);

		if ($oAccount && $this->checkCanChangePassword($oAccount))
		{
			$mResult = $this->сhangePassword($oAccount, $aArguments['NewPassword']);
		}
	
		//return $mResult;
	}

	/**
	 * @param CAccount $oAccount
	 * @return bool
	 */
	protected function checkCanChangePassword($oAccount)
	{
		$bFound = in_array("*", $this->getConfig('SupportedServers', array()));
		
		if (!$bFound)
		{
			$oServer = $this->oMailModule->GetServer($oAccount->ServerId);

			if ($oServer && in_array($oServer->Name, $this->getConfig('SupportedServers')))
			{
				$bFound = true;
			}
		}

		return $bFound;
	}
	
	
	protected function getServerDomain($oAccount)
	{
		$oDomain = null;
		$this->initializeServer();

		if (($oAccount instanceof \CMailAccount) && $this->oBaseApp && $this->oAdminAccount)
		{
			list($sLogin, $sDomainName) = explode('@', $oAccount->Email);

			try
			{
				$oDomain = $this->oBaseApp->Domains->ItemByName($sDomainName);
			}

			catch(Exception $oException) 
			{
				\Aurora\System\Api::Log('Getting domain error');
				\Aurora\System\Api::LogObject($oException);
			}
		}
		
		return $oDomain;
	}
	
	/**
	 * @param CAccount $oAccount
	 */
	protected function сhangePassword($oAccount, $sPassword)
	{
		$mResult = false;
		if (0 < strlen($oAccount->IncomingPassword) && $oAccount->IncomingPassword !== $sPassword)
		{
			$this->initializeServer();
			
			if ($this->oBaseApp && $this->oAdminAccount)
			{
				try
				{
					$oDomain = $this->getServerDomain($oAccount);

					if ($oDomain !== null)
					{
						$sEmail = $oAccount->Email;
						$oServerAccount = $oDomain->Accounts->ItemByAddress($sEmail);

						if ($oServerAccount !== null)
						{
							$oServerAccount->Password = $sPassword;
							$oServerAccount->Save();
							$mResult = true;
						}
					}
				}
				catch (Exception $oException)
				{
					throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Exceptions\Errs::UserManager_AccountNewPasswordUpdateError);
				}
			}
			else 
			{
				throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Exceptions\Errs::UserManager_AccountNewPasswordUpdateError);
			}
		}
		return $mResult;
	}
	
	public function GetSettings()
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::Anonymous);
		
		$sSupportedServers = implode("\n", $this->getConfig('SupportedServers', array()));
		
		$aAppData = array(
			'SupportedServers' => $sSupportedServers,
			'AdminUser' => $this->getConfig('AdminUser', ''),
			'AdminPass' => $this->getConfig('AdminPass', ''),
		);

		return $aAppData;
	}
	
	public function UpdateSettings($SupportedServers, $AdminUser, $AdminPass)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::TenantAdmin);
		
		$aSupportedServers = preg_split('/\r\n|[\r\n]/', $SupportedServers);
		
		$this->setConfig('SupportedServers', $aSupportedServers);
		$this->setConfig('AdminUser', $AdminUser);
		$this->setConfig('AdminPass', $AdminPass);
		$this->saveModuleConfig();
		return true;
	}
}