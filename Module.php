<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\Modules\MailChangePasswordHmailserverPlugin;

/**
 * Allows users to change passwords on their email accounts hosted by [hMailServer](https://www.hmailserver.com/).
 * 
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2023, Afterlogic Corp.
 *
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

	protected $oMailModule;

	/**
	 * @param CApiPluginManager $oPluginManager
	 */
	
	public function init() 
	{
		$this->oBaseApp = null;
		$this->oAdminAccount = null;
		$this->oMailModule = null;
	
		$this->subscribeEvent('Mail::Account::ToResponseArray', array($this, 'onMailAccountToResponseArray'));
		$this->subscribeEvent('Mail::ChangeAccountPassword', array($this, 'onChangeAccountPassword'));
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
				catch(\Exception $oException)
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
	 * Adds to account response array information about if allowed to change the password for this account.
	 * @param array $aArguments
	 * @param mixed $mResult
	 */
	public function onMailAccountToResponseArray($aArguments, &$mResult)
	{
		$oAccount = $aArguments['Account'];

		if ($oAccount && $this->checkCanChangePassword($oAccount))
		{
			if (!isset($mResult['Extend']) || !is_array($mResult['Extend']))
			{
				$mResult['Extend'] = [];
			}
			$mResult['Extend']['AllowChangePasswordOnMailServer'] = true;
		}
	}

	/**
	 * Tries to change password for account if allowed.
	 * @param array $aArguments
	 * @param mixed $mResult
	 */
	public function onChangeAccountPassword($aArguments, &$mResult)
	{
		$bPasswordChanged = false;
		$bBreakSubscriptions = false;
		
		$oAccount = $aArguments['Account'];
		if ($oAccount && $this->checkCanChangePassword($oAccount) && $oAccount->getPassword() === $aArguments['CurrentPassword'])
		{
			$bPasswordChanged = $this->changePassword($oAccount, $aArguments['NewPassword']);
			$bBreakSubscriptions = true; // break if Hmailserver plugin tries to change password in this account. 
		}
		
		if (is_array($mResult))
		{
			$mResult['AccountPasswordChanged'] = $mResult['AccountPasswordChanged'] || $bPasswordChanged;
		}
		
		return $bBreakSubscriptions;
	}
	
	/**
	 * Checks if allowed to change password for account.
	 * @param \Aurora\Modules\Mail\Classes\Account $oAccount
	 * @return bool
	 */
	protected function checkCanChangePassword($oAccount)
	{
		$bFound = in_array("*", $this->getConfig('SupportedServers', array()));
		
		if (!$bFound)
		{
			$oServer = $oAccount->getServer();

			if ($oServer && in_array($oServer->IncomingServer, $this->getConfig('SupportedServers')))
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

		if (($oAccount instanceof \Aurora\Modules\Mail\Models\MailAccount) && $this->oBaseApp && $this->oAdminAccount)
		{
			list($sLogin, $sDomainName) = explode('@', $oAccount->Email);

			try
			{
				$oDomain = $this->oBaseApp->Domains->ItemByName($sDomainName);
			}

			catch(\Exception $oException) 
			{
				\Aurora\System\Api::Log('Getting domain error');
				\Aurora\System\Api::LogObject($oException);
			}
		}
		
		return $oDomain;
	}
	
	/**
	 * Tries to change password for account.
	 * @param \Aurora\Modules\Mail\Classes\Account $oAccount
	 * @param string $sPassword
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ApiException
	 */
	protected function changePassword($oAccount, $sPassword)
	{
		$mResult = false;
		if (0 < strlen($oAccount->getPassword()) && $oAccount->getPassword() !== $sPassword)
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
				catch (\Exception $oException)
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
	
	/**
	 * Obtains list of module settings for super admin.
	 * @return array
	 */
	public function GetSettings()
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);
		
		$sSupportedServers = implode("\n", $this->getConfig('SupportedServers', array()));
		
		$aAppData = array(
			'SupportedServers' => $sSupportedServers,
			'AdminUser' => $this->getConfig('AdminUser', ''),
			'HasAdminPass' => $this->getConfig('AdminPass', '') !== '',
		);

		return $aAppData;
	}
	
	/**
	 * Updates module's super admin settings.
	 * @param string $SupportedServers
	 * @param string $AdminUser
	 * @param int $AdminPass
	 * @return boolean
	 */
	public function UpdateSettings($SupportedServers, $AdminUser, $AdminPass)
	{
		\Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);
		
		$aSupportedServers = preg_split('/\r\n|[\r\n]/', $SupportedServers);
		
		$this->setConfig('SupportedServers', $aSupportedServers);
		$this->setConfig('AdminUser', $AdminUser);
		$this->setConfig('AdminPass', $AdminPass);
		return $this->saveModuleConfig();
	}
}
