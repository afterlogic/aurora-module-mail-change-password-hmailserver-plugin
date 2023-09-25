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
 * @property Settings $oModuleSettings
 *
 * @package Modules
 */
class Module extends \Aurora\System\Module\AbstractModule
{
    /**
     * @var \stdClass
     */
    protected $oBaseApp;

    /**
     * @var \stdClass
     */
    protected $oAdminAccount;

    protected $oMailModule;

    public function init()
    {
        $this->oAdminAccount = null;
        $this->oMailModule = null;

        $this->subscribeEvent('Mail::Account::ToResponseArray', array($this, 'onMailAccountToResponseArray'));
        $this->subscribeEvent('Mail::ChangeAccountPassword', array($this, 'onChangeAccountPassword'));
    }

    /**
     * @return Module
     */
    public static function getInstance()
    {
        return parent::getInstance();
    }

    /**
     * @return Module
     */
    public static function Decorator()
    {
        return parent::Decorator();
    }

    /**
     * @return Settings
     */
    public function getModuleSettings()
    {
        return $this->oModuleSettings;
    }

    /**
     * @return HMailServer
     */
    protected function initializeServer()
    {
        /** @var HMailServer $oBaseApp */
        static $oBaseApp = null;
        $this->checkAndEncryptPassword();

        if (null === $oBaseApp) {
            if (class_exists('COM')) {
                $oBaseApp = new \COM("hMailServer.Application");
                /** @var HMailServer $oBaseApp */
                try {
                    $oBaseApp->Connect();
                    $this->oAdminAccount = $oBaseApp->Authenticate(
                        $this->oModuleSettings->AdminUser,
                        \Aurora\System\Utils::DecryptValue($this->oModuleSettings->AdminPass)
                    );
                } catch(\Exception $oException) {
                    \Aurora\System\Api::Log('Initialize Server Error');
                    \Aurora\System\Api::LogObject($oException);
                }
            } else {
                \Aurora\System\Api::Log('Unable to load class: COM');
            }
        }

        return $oBaseApp;
    }

    /**
     * Adds to account response array information about if allowed to change the password for this account.
     * @param array $aArguments
     * @param mixed $mResult
     */
    public function onMailAccountToResponseArray($aArguments, &$mResult)
    {
        $oAccount = $aArguments['Account'];

        if ($oAccount && $this->checkCanChangePassword($oAccount)) {
            if (!isset($mResult['Extend']) || !is_array($mResult['Extend'])) {
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
        if ($oAccount && $this->checkCanChangePassword($oAccount) && $oAccount->getPassword() === $aArguments['CurrentPassword']) {
            $bPasswordChanged = $this->changePassword($oAccount, $aArguments['NewPassword']);
            $bBreakSubscriptions = true; // break if Hmailserver plugin tries to change password in this account.
        }

        if (is_array($mResult)) {
            $mResult['AccountPasswordChanged'] = $mResult['AccountPasswordChanged'] || $bPasswordChanged;
        }

        return $bBreakSubscriptions;
    }

    /**
     * Checks if allowed to change password for account.
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     * @return bool
     */
    protected function checkCanChangePassword($oAccount)
    {
        $bFound = in_array("*", $this->oModuleSettings->SupportedServers);

        if (!$bFound) {
            $oServer = $oAccount->getServer();

            if ($oServer && in_array($oServer->IncomingServer, $this->oModuleSettings->SupportedServers)) {
                $bFound = true;
            }
        }

        return $bFound;
    }

    protected function getServerDomain($oAccount)
    {
        $oDomain = null;
        $oBaseApp = $this->initializeServer();

        if (($oAccount instanceof \Aurora\Modules\Mail\Models\MailAccount) && $oBaseApp && $this->oAdminAccount) {
            list($sLogin, $sDomainName) = explode('@', $oAccount->Email);

            try {
                $oDomain = $oBaseApp->Domains->ItemByName($sDomainName);
            } catch(\Exception $oException) {
                \Aurora\System\Api::Log('Getting domain error');
                \Aurora\System\Api::LogObject($oException);
            }
        }

        return $oDomain;
    }

    /**
     * Tries to change password for account.
     * @param \Aurora\Modules\Mail\Models\MailAccount $oAccount
     * @param string $sPassword
     * @return boolean
     * @throws \Aurora\System\Exceptions\ApiException
     */
    protected function changePassword($oAccount, $sPassword)
    {
        $mResult = false;
        if (0 < strlen($oAccount->getPassword()) && $oAccount->getPassword() !== $sPassword) {
            $oBaseApp = $this->initializeServer();
            if ($oBaseApp && $this->oAdminAccount) {
                try {
                    $oDomain = $this->getServerDomain($oAccount);

                    if ($oDomain !== null) {
                        $sEmail = $oAccount->Email;
                        $oServerAccount = $oDomain->Accounts->ItemByAddress($sEmail);

                        if ($oServerAccount !== null) {
                            $oServerAccount->Password = $sPassword;
                            $oServerAccount->Save();
                            $mResult = true;
                        }
                    }
                } catch (\Exception $oException) {
                    throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Exceptions\Errs::UserManager_AccountNewPasswordUpdateError);
                }
            } else {
                throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Exceptions\Errs::UserManager_AccountNewPasswordUpdateError);
            }
        }
        return $mResult;
    }

    /**
     * Checks if the password is encrypted and does this if it's not.
     * @return boolean
     */
    protected function checkAndEncryptPassword()
    {
        $performedEncryption = false;

        if ($this->oModuleSettings->AdminPass && !\Aurora\System\Utils::IsEncryptedValue($this->oModuleSettings->AdminPass)) {
            $bPrevState = \Aurora\System\Api::skipCheckUserRole(true);
            $this->Decorator()->UpdateSettings(
                implode("\n", $this->oModuleSettings->SupportedServers),
                $this->oModuleSettings->AdminUser,
                $this->oModuleSettings->AdminPass
            );
            $bPrevState = \Aurora\System\Api::skipCheckUserRole($bPrevState);
            $performedEncryption = true;
        }

        return $performedEncryption;
    }

    /**
     * Obtains list of module settings for super admin.
     * @return array
     */
    public function GetSettings()
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);
        $this->checkAndEncryptPassword();

        $sSupportedServers = implode("\n", $this->oModuleSettings->SupportedServers);

        return array(
            'SupportedServers' => $sSupportedServers,
            'AdminUser' => $this->oModuleSettings->AdminUser,
            'HasAdminPass' => $this->oModuleSettings->AdminPass !== '',
        );
    }

    /**
     * Updates module's super admin settings.
     * @param string $SupportedServers
     * @param string $AdminUser
     * @param string $AdminPass
     * @return boolean
     */
    public function UpdateSettings($SupportedServers, $AdminUser, $AdminPass = null)
    {
        \Aurora\System\Api::checkUserRoleIsAtLeast(\Aurora\System\Enums\UserRole::SuperAdmin);

        $aSupportedServers = preg_split('/\r\n|[\r\n]/', $SupportedServers);

        $this->setConfig('SupportedServers', $aSupportedServers);
        $this->setConfig('AdminUser', $AdminUser);

        if ($AdminPass !== null) {
            $this->setConfig('AdminPass', \Aurora\System\Utils::EncryptValue($AdminPass));
        }

        return $this->saveModuleConfig();
    }
}

class HMailServer
{
    /** @var HMailServerDomains */
    public $Domains;

    public function Connect() {}

    /**
     * @param string $user
     * @param string $pass
     *
     * @return mixed
     */
    public function Authenticate($user, $pass)
    {
        return null;
    }
}

class HMailServerDomains
{
    /**
     * @param string $name
     *
     * @return mixed
     */
    public function ItemByName($name)
    {
        return null;
    }
}
