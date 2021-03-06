<?php
/**
 * File manipulations class
 *
 * @author Lubomir Spacek
 * @license https://opensource.org/licenses/MIT
 * @link https://github.com/heximcz/mxtoolbox
 * @link https://dns-tools.best-hosting.cz/
 */
namespace MxToolbox\FileSystem;

use MxToolbox\Exceptions\MxToolboxLogicException;
use MxToolbox\Exceptions\MxToolboxRuntimeException;

/**
 * Class BlacklistsHostnameFile
 * @package MxToolbox\FileSystem
 */
class BlacklistsHostnameFile
{

    /** @var array blacklists */
    private $blacklistHostNames;

    /** @var string path to blacklist files folder */
    private $blacklistPath;

    /**
     * Get blacklists host names
     *
     * @return array
     * @throws MxToolboxLogicException
     */
    public function getBlacklistsHostNames()
    {
        if (is_array($this->blacklistHostNames) && count($this->blacklistHostNames) > 0)
            return $this->blacklistHostNames;
        throw new MxToolboxLogicException('Array is empty, load blacklist first.');
    }

    /**
     * Load blacklists host names from a file
     *
     * @param string $fileName
     * @throws MxToolboxRuntimeException;
     * @throws MxToolboxLogicException;
     * @return $this
     */
    public function loadBlacklistsFromFile($fileName)
    {
        // user not defined any path
        if (empty($this->blacklistPath))
            $this->setBlacklistFilePath();
        
        $blFile = $this->blacklistPath . $fileName;
        if (!is_readable($blFile))
            throw new MxToolboxRuntimeException("Blacklists file does not exist in: " . $blFile, 400);

        if (!($this->blacklistHostNames = file($blFile, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES)) === false) {
            if (!count($this->blacklistHostNames) > 0) {
                throw new MxToolboxLogicException(sprintf('Blacklist file' . $blFile . ' is empty in %s\%s()',
                    get_class(), __FUNCTION__));
            }
            return $this;
        }
        throw new MxToolboxRuntimeException(sprintf('Cannot get contents from: ' . $blFile . ' in %s\%s()',
            get_class(), __FUNCTION__), 500);
    }

    /**
     * Build new file with alive DNSBLs host names
     *
     * @param array $aliveBlacklists
     * @return $this
     * @throws MxToolboxRuntimeException
     */
    public function makeAliveBlacklistFile(&$aliveBlacklists)
    {
        if (!array_key_exists('blHostName', $aliveBlacklists[0]))
            throw new MxToolboxRuntimeException("Cannot found index ['blHostName'] in array. Build test array first.");

        $blAliveFileTmp = $this->blacklistPath . 'blacklistsAlive.tmp';
        $blAliveFileOrg = $this->blacklistPath . 'blacklistsAlive.txt';

        // create temp file
        if (!@$file = fopen($blAliveFileTmp, 'w'))
            throw new MxToolboxRuntimeException ('Cannot create new file: ' . $blAliveFileTmp);

        foreach ($aliveBlacklists as $blackList) {
            if ($blackList['blResponse']) {
                fwrite($file, $blackList['blHostName'] . PHP_EOL);
            }
        }
        fclose($file);

        // check file size
        if (!filesize($blAliveFileTmp) > 0) {
            @unlink($blAliveFileTmp);
            throw new MxToolboxRuntimeException ('Blacklist temp file is empty: ' . $blAliveFileTmp);
        }
        // create new blacklist file from temp
        if (!rename($blAliveFileTmp, $blAliveFileOrg))
            throw new MxToolboxRuntimeException('Cannot create Alive Blacklist file. Rename the file failed.');

        return $this;
    }

    /**
     * Delete alive blacklists file if exist
     * @return $this
     */
    public function deleteAliveBlacklist()
    {
        if (empty($this->blacklistPath))
            $this->setBlacklistFilePath();
        $blAliveFile = $this->blacklistPath . 'blacklistsAlive.txt';
        if (is_readable($blAliveFile))
            @unlink($blAliveFile);
        return $this;
    }

    /**
     * Set blacklist file path
     * @param string|boolean $path - default FALSE for auto configuration
     * @return $this
     */
    public function setBlacklistFilePath($path=false)
    {
        // user configuration path
        if (is_string($path)) {
            $this->blacklistPath = $path;
            return $this;
        }
        
        // standard composer installation
        $this->blacklistPath = dirname(__FILE__) .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR .
            'mxtoolbox-blacklists' . DIRECTORY_SEPARATOR .
            'mxtoolbox-blacklists' . DIRECTORY_SEPARATOR;
        
        if (!file_exists($this->blacklistPath . 'blacklists.txt')) {
            // install blacklist files directly to mxtoolbox (travis,...)
            $this->blacklistPath = dirname(__FILE__) .
                DIRECTORY_SEPARATOR . '..' .
                DIRECTORY_SEPARATOR . '..' .
                DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR .
                'vendor' . DIRECTORY_SEPARATOR .
                'mxtoolbox-blacklists' . DIRECTORY_SEPARATOR .
                'mxtoolbox-blacklists' . DIRECTORY_SEPARATOR;
            if (!file_exists($this->blacklistPath . 'blacklists.txt')) {
                throw new MxToolboxRuntimeException('Standard path to the blacklist file not exist.');
            }
        }
        return $this;
    }

}
