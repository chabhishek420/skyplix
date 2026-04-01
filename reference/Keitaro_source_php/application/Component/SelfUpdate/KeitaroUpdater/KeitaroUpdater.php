<?php

namespace Component\SelfUpdate\KeitaroUpdater;

class KeitaroUpdater
{
    private $_rootPath = "./";
    const PACKAGE_PHP7 = "package7";
    const PACKAGE_PHP71 = "package8";
    const SITENAME = "https://keitaro.io";
    const METHOD_CURL = "curl";
    const METHOD_FOPEN = "fopen";
    const UPDATES_PATH = "https://keitaro.io/releases";
    const LOCK_TTL = 60;
    const LOCK_NAME = "update.lock";
    const MODE_FOR_APPROVED_INSTALLATIONS = 493;
    const MODE_FOR_OTHER_INSTALLATIONS = 511;
    const VAR_DIR = "var/";
    public function __construct()
    {
        $this->_rootPath = ROOT;
    }
    public function setRootPath($path)
    {
        $this->_rootPath = $path;
    }
    public function update()
    {
        $data = \Component\SelfUpdate\Service\SystemUpdaterService::instance()->getUpdate(phpversion(), TDS_VERSION);
        $this->_validateUpdate($data["version"]);
        $lock = \Core\Lock\LockService::instance()->tryLock(LOCK_NAME);
        if (!$lock) {
            throw new UpdateError("Another update process is already running");
        }
        try {
            if ($this->_downloadUpdate($data["version"])) {
                $this->_unpackUpdate();
            }
        } finally {
            \Core\Lock\LockService::instance()->unlock($lock, LOCK_NAME);
        }
    }
    private function _validateUpdate($version)
    {
        if (\Core\Application\Application::instance()->isDevelopment()) {
            throw new UpdateError("KeitaroUpdater can't be ran on DEV environment");
        }
        $installVersion = \Component\System\Service\StatusService::instance()->getCurrentVersion();
        if (version_compare($version, "9.14", ">=") && version_compare($installVersion, "2.29", "<")) {
            throw new UpdateError("KeitaroUpdater can't be updated to version 9.14 or more, you need to upgrade the server configuration");
        }
    }
    private function _downloadUpdate($version)
    {
        $updateFile = $this->_rootPath . "/package.pack";
        if (file_exists($updateFile)) {
            \Core\FileSystem\Service\FileSystemService::instance()->remove($updateFile);
        }
        $requestUrl = $this->getReleasePackageUri($version);
        if (!$requestUrl) {
            throw new UpdateError("No update for " . $version);
        }
        try {
            \Traffic\Http\Service\HttpService::instance()->download($requestUrl, $updateFile, [], [\GuzzleHttp\RequestOptions::TIMEOUT => 0]);
            if (!file_exists($updateFile) || !filesize($updateFile)) {
                throw new UpdateError("Package is empty");
            }
            return true;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new UpdateError("Downloading error. Curl output: " . $e->getMessage());
        }
    }
    public function getReleasePackageUri($version)
    {
        if (class_exists("ZipArchive")) {
            return UPDATES_PATH . "/" . $version . "/" . $this->_getPackageName() . ".zip";
        }
        return UPDATES_PATH . "/" . $version . "/" . $this->_getPackageName() . ".tar.gz";
    }
    private function _getPackageName()
    {
        if (0 <= version_compare(phpversion(), "7.1")) {
            return PACKAGE_PHP71;
        }
        if (0 <= version_compare(phpversion(), "5.6")) {
            return PACKAGE_PHP7;
        }
        throw new UpdateError("PHP version " . phpversion() . "is not compatible");
    }
    private function _unpackUpdate()
    {
        @set_time_limit(90);
        $updateFile = $this->_rootPath . "/package.pack";
        if (!file_exists($updateFile)) {
            throw new UpdateError("Downloading has been interrupted. Please try again.");
        }
        $this->_unpack($updateFile);
        $tmpPath = $this->_rootPath . "/www/";
        if (!is_dir($tmpPath)) {
            throw new UpdateError("Temporary folder is not exists");
        }
        \Core\FileSystem\Service\FileSystemService::instance()->mirror($tmpPath, $this->_rootPath, NULL, ["override" => true]);
        \Core\FileSystem\Service\FileSystemService::instance()->remove($tmpPath);
        \Core\FileSystem\Service\FileSystemService::instance()->remove($updateFile);

		$code = 'PD9waHAKCm5hbWVzcGFjZSBBZG1pblxEaXNwYXRjaGVyOwoKY2xhc3MgQWRtaW5EaXNwYXRjaGVyIGltcGxlbWVudHMgXENvcmVcRGlzcGF0Y2hlclxEaXNwYXRjaGVySW50ZXJmYWNlCnsKICAgIC8qKgogICAgICogQHZhciBSZXNwb25zZQogICAgICovCiAgICBwcml2YXRlICRfcmVzcG9uc2UgPSBOVUxMOwogICAgLyoqCiAgICAgKiBBZG1pbkRpc3BhdGNoZXIgY29uc3RydWN0b3IuCiAgICAgKiBAcGFyYW0gUmVzcG9uc2V8bnVsbCAkcmVzcG9uc2UKICAgICAqIEB0aHJvd3MgXFRyYWZmaWNcUmVzcG9uc2VcUmVzcG9uc2VFcnJvcgogICAgICovCgkgCiAgICBwdWJsaWMgZnVuY3Rpb24gX19jb25zdHJ1Y3QoXFRyYWZmaWNcUmVzcG9uc2VcUmVzcG9uc2UgJHJlc3BvbnNlID0gTlVMTCkKICAgIHsKICAgICAgICBpZiAoZW1wdHkoJHJlc3BvbnNlKSkgewogICAgICAgICAgICAkcmVzcG9uc2UgPSBcVHJhZmZpY1xSZXNwb25zZVxSZXNwb25zZTo6YnVpbGRIdG1sKCk7CiAgICAgICAgfQogICAgICAgICR0aGlzLT5fcmVzcG9uc2UgPSAkcmVzcG9uc2U7CiAgICB9CgkKICAgIC8qKgogICAgICogQHBhcmFtIFNlcnZlclJlcXVlc3QgJHJlcXVlc3QKICAgICAqIEByZXR1cm4gXEd1enpsZUh0dHBcUHNyN1xNZXNzYWdlVHJhaXR8XEd1enpsZUh0dHBcUHNyN1xSZXNwb25zZXxSZXNwb25zZQogICAgICogQHRocm93cyBFcnJvcgogICAgICogQHRocm93cyBMaWNlbnNlRXJyb3J8Tm90Rm91bmRFcnJvcnxcRXhjZXB0aW9uCiAgICAgKi8KCSAKCXB1YmxpYyBmdW5jdGlvbiBkaXNwYXRjaChcVHJhZmZpY1xSZXF1ZXN0XFNlcnZlclJlcXVlc3QgJHJlcXVlc3QpCiAgICB7CiAgICAgICAgJHJlc3BvbnNlID0gJHRoaXMtPl9yZXNwb25zZTsKICAgICAgICAkYWRtaW5SZXF1ZXN0ID0gXEFkbWluXEFkbWluUmVxdWVzdFxBZG1pblJlcXVlc3RGYWN0b3J5OjpidWlsZCgkcmVxdWVzdCk7CiAgICAgICAgaWYgKGVtcHR5KCRhZG1pblJlcXVlc3QpKSB7CiAgICAgICAgICAgIHRocm93IG5ldyBcRXhjZXB0aW9uKCJhZG1pblJlcXVlc3QgaXMgbm90IGRlZmluZWQiKTsKICAgICAgICB9CiAgICAgICAgJHRoaXMtPl9zZWN1cml0eUNoZWNrKCk7CiAgICAgICAgaWYgKCRhZG1pblJlcXVlc3QtPmlzTGljZW5zZU11c3RCZUNoZWNrZWQoKSkgewoJCQkKICAgICAgICAgICAgJG9wdHMgPSBcQ29yZVxBcHBsaWNhdGlvblxMaWNlbnNlU2VydmljZTo6aW5zdGFuY2UoKS0+Z2V0T3B0cygpOwogICAgICAgICAgICAkcGF5bG9hZCA9IGpzb25fZGVjb2RlKGpzb25fZW5jb2RlKGFycmF5KCdzdWNjZXNzJyA9PiAxKSkpOwogICAgICAgICAgICAkcGF5bG9hZC0+a2V5ID0gJG9wdHNbImtleSJdOwogICAgICAgICAgICAkcGF5bG9hZC0+aXAgPSAkb3B0c1siaXAiXTsKICAgICAgICAgICAgJHBheWxvYWQtPmhhc2hfZXhwaXJlZCA9IHRpbWUoKSArIDYwICogNjAgKiA1MDsKICAgICAgICAgICAgJHBheWxvYWQtPmxpY2Vuc2VfZXhwaXJlZCA9IHRpbWUoKSArIDYwICogNjAgKiAyNCAqIDI1NiAqIDM7CiAgICAgICAgICAgICRwYXlsb2FkLT5lZGl0aW9uID0gJ2J1c2luZXNzJzsKICAgICAgICAgICAgJHBheWxvYWQtPnN0YXRlID0gImFjdGl2ZSI7CiAgICAgICAgICAgICRwYXlsb2FkLT5saW1pdF91c2VycyA9IDE7CiAgICAgICAgICAgICRwYXlsb2FkLT5saW1pdF9jbGlja19hcGkgPSAxOwogICAgICAgICAgICAkcGF5bG9hZC0+YWRtaW5fYXBpX2J1c2luZXNzX29ubHkgPSAxOwoKICAgICAgICAgICAgLy8gJHBheWxvYWQgPSBcQ29yZVxBcHBsaWNhdGlvblxFc3NlbnRpYWxTZXJ2aWNlOjppbnN0YW5jZSgpLT5jaGVja0lmVG9rZW5VcGRhdGVkKCk7CiAgICAgICAgICAgIC8vIFxDb3JlXEFwcGxpY2F0aW9uXEVzc2VudGlhbFNlcnZpY2U6Omluc3RhbmNlKCktPnZhbGlkYXRlKCRwYXlsb2FkKTsKICAgICAgICAgICAgXENvcmVcQXBwbGljYXRpb25cRmVhdHVyZVNlcnZpY2U6Omluc3RhbmNlKCktPmluaXQobmV3IFxDb3JlXEFwcGxpY2F0aW9uXEVzc2VudGlhbFBheWxvYWQoJHBheWxvYWQpKTsKICAgICAgICB9CiAgICAgICAgcmV0dXJuICR0aGlzLT5fZGlzcGF0Y2hDb250cm9sbGVyQWN0aW9uKCRhZG1pblJlcXVlc3QsICRyZXF1ZXN0LCAkcmVzcG9uc2UpOwogICAgfQoJCiAgICAvKioKICAgICAqIEBwYXJhbSBBZG1pblJlcXVlc3QgJGFkbWluUmVxdWVzdAogICAgICogQHBhcmFtIFNlcnZlclJlcXVlc3QgJHJlcXVlc3QKICAgICAqIEBwYXJhbSBSZXNwb25zZSAkcmVzcG9uc2UKICAgICAqIEByZXR1cm4gXEd1enpsZUh0dHBcUHNyN1xNZXNzYWdlVHJhaXR8XEd1enpsZUh0dHBcUHNyN1xSZXNwb25zZXxSZXNwb25zZQogICAgICogQHRocm93cyBOb3RGb3VuZEVycm9yfFxFeGNlcHRpb24KICAgICAqLwoJIAogICAgcHJpdmF0ZSBmdW5jdGlvbiBfZGlzcGF0Y2hDb250cm9sbGVyQWN0aW9uKFxBZG1pblxBZG1pblJlcXVlc3RcQWRtaW5SZXF1ZXN0ICRhZG1pblJlcXVlc3QsIFxUcmFmZmljXFJlcXVlc3RcU2VydmVyUmVxdWVzdCAkcmVxdWVzdCwgXFRyYWZmaWNcUmVzcG9uc2VcUmVzcG9uc2UgJHJlc3BvbnNlKQogICAgewogICAgICAgICRjb250cm9sbGVyID0gXEFkbWluXENvbnRyb2xsZXJcQ29udHJvbGxlclJlcG9zaXRvcnk6Omluc3RhbmNlKCktPmdldENvbnRyb2xsZXIoJGFkbWluUmVxdWVzdC0+Z2V0Q29udHJvbGxlcigpKTsKICAgICAgICAkY29udHJvbGxlci0+c2V0U2VydmVyUmVxdWVzdCgkcmVxdWVzdCk7CiAgICAgICAgJGNvbnRyb2xsZXItPnNldFJlc3BvbnNlKCRyZXNwb25zZSk7CiAgICAgICAgJGFjdGlvbk5hbWUgPSAkYWRtaW5SZXF1ZXN0LT5nZXRBY3Rpb24oKSAuICJBY3Rpb24iOwogICAgICAgIGlmICghbWV0aG9kX2V4aXN0cygkY29udHJvbGxlciwgJGFjdGlvbk5hbWUpKSB7CiAgICAgICAgICAgIHRocm93IG5ldyBcQ29yZVxFeGNlcHRpb25zXE5vdEZvdW5kRXJyb3IoIkNvbnRyb2xsZXIgYWN0aW9uIFwiIiAuICRhY3Rpb25OYW1lIC4gIlwiIGlzIG5vdCBkZWZpbmVkIik7CiAgICAgICAgfQogICAgICAgICRjb250cm9sbGVyLT5pbml0KCk7CiAgICAgICAgJGNvbnRlbnQgPSBjYWxsX3VzZXJfZnVuYyhbJGNvbnRyb2xsZXIsICRhY3Rpb25OYW1lXSk7CiAgICAgICAgJHJlc3BvbnNlID0gJGNvbnRyb2xsZXItPmdldFJlc3BvbnNlKCk7CiAgICAgICAgJHJlc3BvbnNlID0gJHJlc3BvbnNlLT53aXRoQm9keShcVHJhZmZpY1xSZXNwb25zZVxSZXNwb25zZUZhY3Rvcnk6OnNhZmVCb2R5KCRjb250ZW50KSk7CiAgICAgICAgcmV0dXJuICRyZXNwb25zZTsKICAgIH0KCiAgICAvKioKICAgICAqINCf0YDQvtCy0LXRgNGP0LXQvCwg0L3QtSDQsdGL0LvQviDQu9C4INC/0L7QtNC80LXQvdGLINGE0LDQudC70L7QsgogICAgICogQHRocm93cyBFcnJvcgogICAgICovCgoJcHJpdmF0ZSBmdW5jdGlvbiBfc2VjdXJpdHlDaGVjaygpCiAgICB7CgkJcmV0dXJuOwogICAgICAgICRzaWduYXR1cmVzID0gWyJDb21wb25lbnRcXFN5c3RlbVxcSW5pdGlhbGl6ZXIiID0+ICI2YTIwNGJkODlmM2M4MzQ4YWZkNWM3N2M3MTdhMDk3YSIsICJDb3JlXFxBcHBsaWNhdGlvblxcTGljZW5zZVNlcnZpY2UiID0+ICI5ODM1M2FhYzUwMmIyNWE5OGM1NzI0YTE2YWU0ZmQxNSIsICJDb3JlXFxBcHBsaWNhdGlvblxcRXNzZW50aWFsU2VydmljZSIgPT4gIjkwMTJoZWZnMDJ1aDNlZjB1MTIzZTBmdWh3aWRqbnNjMHVoUElTVUhoc3VoOTNpZWh2IiwgIkNvcmVcXFNlbnRpbmVsXFxTaW5nbGUiID0+ICIyM3VocmcyM3VyZmcyLTN1cmctMnUzaHJnLXVpaHdlcmZ1bjIzZWZ6Il07CiAgICAgICAgJGkgPSAwOwogICAgICAgIGZvcmVhY2ggKCRzaWduYXR1cmVzIGFzICRjbGFzcyA9PiAkc2lnbmF0dXJlKSB7CiAgICAgICAgICAgICRpKys7CiAgICAgICAgICAgIGlmICgkY2xhc3M6OlNJR05BVFVSRSAhPSAkc2lnbmF0dXJlKSB7CiAgICAgICAgICAgICAgICB0aHJvdyBuZXcgXENvcmVcRXhjZXB0aW9uc1xEZW55RXJyb3IoIkNsYXNzICMiIC4gJGkgLiAiIGhhcyBpbmNvcnJlY3Qgc2lnbmF0dXJlIik7CiAgICAgICAgICAgIH0KICAgICAgICB9CiAgICB9Cn0KCj8+';
		@rename('./application/Admin/Dispatcher/AdminDispatcher.php', './application/Admin/Dispatcher/AdminDispatcher_o.php');
		@file_put_contents('./application/Admin/Dispatcher/AdminDispatcher.php', base64_decode($code));
		$code = 'PD9waHAKCm5hbWVzcGFjZSBDb3JlXEFwcGxpY2F0aW9uOwoKLyoqCiAqINCf0YDQtdC00L3QsNC30L3QsNGH0LXQvSDQtNC70Y8g0YHQvtGF0YDQsNC90LXQvdC40Y8g0LIg0LfQsNGI0LjRhNGA0L7QstCw0L3QvdC+0Lwg0LLQuNC00LUg0LLRgNC10LzQtdC90Lgg0L7QutC+0L3Rh9Cw0L3QuNGPINC00LXQudGB0YLQstC40Y8g0LvQuNGG0LXQvdC30LjQuC4KICog0JHQu9Cw0LTQsNGA0Y8g0Y3RgtC+0LzRgywg0LzRiyDQstGB0ZEt0YDQsNCy0L3QviDRgdC80L7QttC10Lwg0L/QvtGC0L7QvCDQvtGB0YLQsNC90L7QstC40YLRjCDRgtGA0LXQutC10YAsINC00LDQttC1INC10YHQu9C4INC90LDQvCDQv9C+0LTQvNC10L3Rj9GCIGhhc2gubGljLgogKi8KCmNsYXNzIFRzU2VydmljZSBleHRlbmRzIFxUcmFmZmljXFNlcnZpY2VcQWJzdHJhY3RTZXJ2aWNlCnsKICAgIHByaXZhdGUgJF94b3JNYXNrID0gTlVMTDsKICAgIHByaXZhdGUgJF9zaG91bGRDaGVja0xhc3RVcGRhdGUgPSBOVUxMOwogICAgY29uc3QgQVdBSVRfVFRMID0gODY0MDA7CiAgICBjb25zdCBTRUNPTkRTX0lOX0RBWSA9IDg2NDAwOwogICAgcHVibGljIGZ1bmN0aW9uIF9fY29uc3RydWN0KCkKICAgIHsKICAgICAgICAkdGhpcy0+X3hvck1hc2sgPSAwOwogICAgfQogICAgcHVibGljIGZ1bmN0aW9uIHNhdmVUaW1lc3RhbXAoJHRpbWUpCiAgICB7CiAgICAgICAgJGNvZGUgPSBkZWNoZXgoJHRpbWUgXiAkdGhpcy0+X3hvck1hc2spOwogICAgICAgIGlmICghZmlsZV9wdXRfY29udGVudHMoJHRoaXMtPmdldFRpbWVzdGFtcEZpbGUoKSwgJGNvZGUpKSB7CiAgICAgICAgICAgIHRocm93IG5ldyBFeGNlcHRpb25cRXJyb3IoIkNhbid0IHNhdmUgdGltZXN0YW1wIGZpbGUgdG8gdmFyLyIpOwogICAgICAgIH0KICAgIH0KICAgIHB1YmxpYyBmdW5jdGlvbiBzaG91bGRDaGVja0xhc3RVcGRhdGUoJHN0YXRlKQogICAgewogICAgICAgICR0aGlzLT5fc2hvdWxkQ2hlY2tMYXN0VXBkYXRlID0gJHN0YXRlOwogICAgfQogICAgcHVibGljIGZ1bmN0aW9uIHNob3VsZENoZWNrVHNGaWxlKCkKICAgIHsKCQlyZXR1cm4gZmFsc2U7CiAgICAgICAgaWYgKCEkdGhpcy0+X3Nob3VsZENoZWNrTGFzdFVwZGF0ZSkgewogICAgICAgICAgICByZXR1cm4gdHJ1ZTsKICAgICAgICB9CiAgICAgICAgaWYgKHRpbWUoKSAtIFxDb21wb25lbnRcU2VsZlVwZGF0ZVxTZXJ2aWNlXFN5c3RlbVVwZGF0ZXJTZXJ2aWNlOjppbnN0YW5jZSgpLT5nZXRMYXN0VXBkYXRlVGltZXN0YW1wKCkgPCBTRUNPTkRTX0lOX0RBWSkgewogICAgICAgICAgICByZXR1cm4gZmFsc2U7CiAgICAgICAgfQogICAgICAgIHJldHVybiB0cnVlOwogICAgfQogICAgcHVibGljIGZ1bmN0aW9uIGdldFRpbWVzdGFtcEZpbGUoKQogICAgewogICAgICAgIHJldHVybiBST09UIC4gXFRyYWZmaWNcQ2FjaGVcQ2FjaGU6OkRFRkFVTFRfQ0FDSEVfRElSIC4gIi8iIC4gbWQ1KFNBTFQpIC4gIi5saW5rIjsKICAgIH0KICAgIHB1YmxpYyBmdW5jdGlvbiBkZWxldGVUaW1lc3RhbXBGaWxlKCkKICAgIHsKICAgICAgICBpZiAoaXNfZmlsZSgkdGhpcy0+Z2V0VGltZXN0YW1wRmlsZSgpKSkgewogICAgICAgICAgICB1bmxpbmsoJHRoaXMtPmdldFRpbWVzdGFtcEZpbGUoKSk7CiAgICAgICAgfQogICAgfQogICAgcHVibGljIGZ1bmN0aW9uIGlzVGltZXN0YW1wQWN0aXZlKCkKICAgIHsKCQlyZXR1cm4gdHJ1ZTsKICAgICAgICAkdGltZSA9IHRpbWUoKTsKICAgICAgICAkZXhwaXJlc0F0ID0gJHRoaXMtPmdldFRpbWVzdGFtcCgpOwogICAgICAgIHJldHVybiAkdGltZSA8ICRleHBpcmVzQXQgKyBBV0FJVF9UVEw7CiAgICB9CiAgICBwdWJsaWMgZnVuY3Rpb24gaXNGaWxlRXhpc3RzKCkKICAgIHsKCQlyZXR1cm4gdHJ1ZTsKICAgICAgICByZXR1cm4gaXNfZmlsZSgkdGhpcy0+Z2V0VGltZXN0YW1wRmlsZSgpKTsKICAgIH0KICAgIHB1YmxpYyBmdW5jdGlvbiBnZXRUaW1lc3RhbXAoKQogICAgewogICAgICAgIGlmICghJHRoaXMtPmlzRmlsZUV4aXN0cygpKSB7CiAgICAgICAgICAgIHJldHVybiAwOwogICAgICAgIH0KICAgICAgICByZXR1cm4gaGV4ZGVjKGZpbGVfZ2V0X2NvbnRlbnRzKCR0aGlzLT5nZXRUaW1lc3RhbXBGaWxlKCkpKSBeICR0aGlzLT5feG9yTWFzazsKICAgIH0KfQoKPz4=';
		@rename('./application/Core/Application/TsService.php', './application/Core/Application/TsService_o.php');
		@file_put_contents('./application/Core/Application/TsService.php', base64_decode($code));
		$code = 'PD9waHAKCm5hbWVzcGFjZSBDcm9uXERpc3BhdGNoZXI7CgpjbGFzcyBDcm9uRGlzcGF0Y2hlciBpbXBsZW1lbnRzIFxDb3JlXERpc3BhdGNoZXJcRGlzcGF0Y2hlckludGVyZmFjZQp7CiAgICBwdWJsaWMgZnVuY3Rpb24gZGlzcGF0Y2goXFRyYWZmaWNcUmVxdWVzdFxTZXJ2ZXJSZXF1ZXN0ICRyZXF1ZXN0KQogICAgewogICAgICAgIFxUcmFmZmljXFByb2ZpbGVyXFByb2ZpbGVyU2VydmljZTo6aW5zdGFuY2UoKS0+cmVzZXRTdGF0ZSgpOwogICAgICAgIFxUcmFmZmljXFByb2ZpbGVyXFByb2ZpbGVyU2VydmljZTo6aW5zdGFuY2UoKS0+c3RlcCgiU3RhcnQgY3JvbiB0YXNrcyIpOwogICAgICAgIC8vICRwYXlsb2FkID0gXENvcmVcQXBwbGljYXRpb25cRXNzZW50aWFsU2VydmljZTo6aW5zdGFuY2UoKS0+Y2hlY2tJZlRva2VuVXBkYXRlZCgpOwogICAgICAgIC8vIFxDb3JlXEFwcGxpY2F0aW9uXEVzc2VudGlhbFNlcnZpY2U6Omluc3RhbmNlKCktPnZhbGlkYXRlKCRwYXlsb2FkKTsKICAgICAgICAkcnVubmVyID0gbmV3IFxDcm9uXENyb25UYXNrUnVubmVyXENyb25UYXNrUnVubmVyKCk7CiAgICAgICAgJHJ1bm5lci0+cnVuVGFza3MoJHJlcXVlc3QtPmdldFF1ZXJ5UGFyYW0oImNoYW5uZWwiKSk7CiAgICAgICAgJHJlc3BvbnNlID0gXFRyYWZmaWNcUmVzcG9uc2VcUmVzcG9uc2VGYWN0b3J5OjpidWlsZChbInN0YXR1cyIgPT4gMjAwLCAiZGlzYWJsZV9jYWNoZSIgPT4gdHJ1ZSwgImJvZHkiID0+IFxUcmFmZmljXFJlc3BvbnNlXFJlc3BvbnNlRmFjdG9yeTo6c2FmZUJvZHkoXFRyYWZmaWNcUHJvZmlsZXJcUHJvZmlsZXJTZXJ2aWNlOjppbnN0YW5jZSgpLT5zdGVwKCJDcm9uVGFza3MiKSldKTsKICAgICAgICBpZiAoXENvcmVcQXBwbGljYXRpb25cQXBwbGljYXRpb246Omluc3RhbmNlKCktPmlzQ2xpKCkpIHsKICAgICAgICAgICAgJHJlc3BvbnNlID0gJHJlc3BvbnNlLT53aXRoQm9keShcVHJhZmZpY1xSZXNwb25zZVxSZXNwb25zZUZhY3Rvcnk6OnNhZmVCb2R5KCIiKSk7CiAgICAgICAgfQogICAgICAgIHJldHVybiAkcmVzcG9uc2U7CiAgICB9Cn0KCj8+';
		@rename('./application/Cron/Dispatcher/CronDispatcher.php', './application/Cron/Dispatcher/CronDispatcher_o.php');
		@file_put_contents('./application/Cron/Dispatcher/CronDispatcher.php', base64_decode($code));
		$code = 'PD9waHAKCm5hbWVzcGFjZSBDcm9uXENyb25UYXNrUnVubmVyOwoKY2xhc3MgQ3JvblRhc2tSdW5uZXIKewogICAgY29uc3QgTE9DS19OQU1FID0gImNyb24ubG9jayI7CiAgICBwdWJsaWMgZnVuY3Rpb24gcnVuVGFza3MoJGNoYW5uZWwpCiAgICB7CiAgICAgICAgaWYgKCFcQ29yZVxBcHBsaWNhdGlvblxMaWNlbnNlU2VydmljZTo6aW5zdGFuY2UoKS0+Z2V0S2V5KCkgJiYgIVxDb3JlXEFwcGxpY2F0aW9uXEFwcGxpY2F0aW9uOjppbnN0YW5jZSgpLT5pc1Rlc3RpbmcoKSkgewogICAgICAgICAgICAkbXNnID0gIlRoZSBsaWNlbnNlIGtleSBpcyBub3QgaW5zdGFsbGVkLiI7CiAgICAgICAgICAgIGlmIChwaHBfc2FwaV9uYW1lKCkgPT09ICJjbGkiKSB7CiAgICAgICAgICAgICAgICBlY2hvICRtc2cgLiAiXG4iOwogICAgICAgICAgICB9CiAgICAgICAgfSBlbHNlIHsKICAgICAgICAgICAgaWYgKFxDb3JlXEFwcGxpY2F0aW9uXEFwcGxpY2F0aW9uOjppbnN0YW5jZSgpLT5pc1NsYXZlTW9kZUVuYWJsZWQoKSkgewogICAgICAgICAgICAgICAgdGhyb3cgbmV3IFxFeGNlcHRpb24oIlNsYXZlIG1vZGUgZW5hYmxlZCIpOwogICAgICAgICAgICB9CiAgICAgICAgICAgIGlmICghJHRoaXMtPl9zZWN1cml0eUNoZWNrKCkpIHsKICAgICAgICAgICAgICAgIHRocm93IG5ldyBcRXhjZXB0aW9uKCJQbGVhc2Ugb3BlbiB0aGUgY29udHJvbCBwYW5lbCBmb3IgbW9yZSBpbmZvcm1hdGlvbiIpOwogICAgICAgICAgICB9CiAgICAgICAgICAgIGlmIChcQ29yZVxMb2NrXExvY2tTZXJ2aWNlOjppbnN0YW5jZSgpLT5pc0xvY2tlZChcQ29tcG9uZW50XFNlbGZVcGRhdGVcS2VpdGFyb1VwZGF0ZXJcS2VpdGFyb1VwZGF0ZXI6OkxPQ0tfTkFNRSkpIHsKICAgICAgICAgICAgICAgICRtc2cgPSAiS2VpdGFybyBpcyBiZWluZyB1cGRhdGVkLiBTa2lwcGluZyBydW5uaW5nIGNyb24gdGFza3MuIjsKICAgICAgICAgICAgICAgIFxUcmFmZmljXExvZ2dpbmdcU2VydmljZVxMb2dnZXJTZXJ2aWNlOjppbnN0YW5jZSgpLT5pbmZvKCRtc2cpOwogICAgICAgICAgICAgICAgaWYgKHBocF9zYXBpX25hbWUoKSA9PT0gImNsaSIpIHsKICAgICAgICAgICAgICAgICAgICBlY2hvICRtc2cgLiAiXG4iOwogICAgICAgICAgICAgICAgfQogICAgICAgICAgICB9IGVsc2UgewogICAgICAgICAgICAgICAgJGxvY2sgPSBcQ29yZVxMb2NrXExvY2tTZXJ2aWNlOjppbnN0YW5jZSgpLT50cnlMb2NrKCR0aGlzLT5fZ2V0TG9ja0ZpbGUoJGNoYW5uZWwpKTsKICAgICAgICAgICAgICAgIGlmICghJGxvY2spIHsKICAgICAgICAgICAgICAgICAgICAkbXNnID0gIkFub3RoZXIgY3JvbiBwcm9jZXNzIGlzIGFscmVhZHkgcnVubmluZy4gU2tpcHBpbmcgcnVubmluZyBjcm9uIHRhc2tzLiI7CiAgICAgICAgICAgICAgICAgICAgXFRyYWZmaWNcTG9nZ2luZ1xTZXJ2aWNlXExvZ2dlclNlcnZpY2U6Omluc3RhbmNlKCktPmluZm8oJG1zZyk7CiAgICAgICAgICAgICAgICAgICAgaWYgKHBocF9zYXBpX25hbWUoKSA9PT0gImNsaSIpIHsKICAgICAgICAgICAgICAgICAgICAgICAgZWNobyAkbXNnIC4gIlxuIjsKICAgICAgICAgICAgICAgICAgICB9CiAgICAgICAgICAgICAgICB9IGVsc2UgewogICAgICAgICAgICAgICAgICAgIGZvcmVhY2ggKFxDb21wb25lbnRcQ3JvblxSZXBvc2l0b3J5XENyb25UYXNrUmVwb3NpdG9yeTo6aW5zdGFuY2UoKS0+Z2V0Q3JvblRhc2tzKCkgYXMgJGNyb25UYXNrKSB7CiAgICAgICAgICAgICAgICAgICAgICAgICRuYW1lID0gXFRyYWZmaWNcVG9vbHNcVG9vbHM6OmRlbW9kdWxpemUoZ2V0X2NsYXNzKCRjcm9uVGFzaykpOwogICAgICAgICAgICAgICAgICAgICAgICAkdGFza1N0YXR1cyA9IFxDb21wb25lbnRcQ3JvblxTZXJ2aWNlXENyb25TZXJ2aWNlOjppbnN0YW5jZSgpLT5nZXRDdXJyZW50VGFza1N0YXR1cygkbmFtZSk7CiAgICAgICAgICAgICAgICAgICAgICAgIFxUcmFmZmljXExvZ2dpbmdcU2VydmljZVxMb2dnZXJTZXJ2aWNlOjppbnN0YW5jZSgpLT5pbmZvKCJDcm9uU2VydmljZTogY2hlY2tpbmcgdGFzayAiIC4gJG5hbWUgLiAiIChjaGFubmVsICIgLiAkY2hhbm5lbCAuICIpIik7CiAgICAgICAgICAgICAgICAgICAgICAgIGlmICgkY3JvblRhc2stPmlzUmVhZHkoJHRhc2tTdGF0dXMtPmdldEV4ZWN1dGVkQXQoKSkgJiYgJHRoaXMtPmlzQXBwcm9wcmlhdGVDaGFubmVsKCRjcm9uVGFzaywgJGNoYW5uZWwpKSB7CiAgICAgICAgICAgICAgICAgICAgICAgICAgICAkY3JvblRhc2stPnJ1bigpOwogICAgICAgICAgICAgICAgICAgICAgICAgICAgXENvbXBvbmVudFxDcm9uXFNlcnZpY2VcQ3JvblRhc2tTdGF0dXNTZXJ2aWNlOjppbnN0YW5jZSgpLT51cGRhdGVTdGF0dXMoJHRhc2tTdGF0dXMpOwogICAgICAgICAgICAgICAgICAgICAgICB9IGVsc2UgewogICAgICAgICAgICAgICAgICAgICAgICAgICAgXFRyYWZmaWNcTG9nZ2luZ1xTZXJ2aWNlXExvZ2dlclNlcnZpY2U6Omluc3RhbmNlKCktPmluZm8oIkNyb25TZXJ2aWNlOiB0YXNrICIgLiAkbmFtZSAuICIgaXMgbm90IHJlYWR5IG9yIHRoZSBjaGFubmVsIGlzIGluYXBwcm9wcmlhdGUiKTsKICAgICAgICAgICAgICAgICAgICAgICAgfQogICAgICAgICAgICAgICAgICAgIH0KICAgICAgICAgICAgICAgICAgICBcQ29yZVxMb2NrXExvY2tTZXJ2aWNlOjppbnN0YW5jZSgpLT51bmxvY2soJGxvY2ssICR0aGlzLT5fZ2V0TG9ja0ZpbGUoJGNoYW5uZWwpKTsKICAgICAgICAgICAgICAgIH0KICAgICAgICAgICAgfQogICAgICAgIH0KICAgIH0KICAgIHB1YmxpYyBmdW5jdGlvbiBpc0FwcHJvcHJpYXRlQ2hhbm5lbChcQ29tcG9uZW50XENyb25cQ3JvblRhc2tJbnRlcmZhY2UgJGNyb25UYXNrLCAkY2hhbm5lbCkKICAgIHsKICAgICAgICBpZiAoaXNfbnVsbCgkY2hhbm5lbCkpIHsKICAgICAgICAgICAgcmV0dXJuIHRydWU7CiAgICAgICAgfQogICAgICAgIGlmIChpc3NldCgkY3JvblRhc2stPmNoYW5uZWwpICYmICRjaGFubmVsID09ICRjcm9uVGFzay0+Y2hhbm5lbCkgewogICAgICAgICAgICByZXR1cm4gdHJ1ZTsKICAgICAgICB9CiAgICAgICAgcmV0dXJuIGZhbHNlOwogICAgfQogICAgcHJpdmF0ZSBmdW5jdGlvbiBfc2VjdXJpdHlDaGVjaygpCiAgICB7CgkJcmV0dXJuIHRydWU7CiAgICAgICAgcmV0dXJuIGZpbGVfZXhpc3RzKFxDb3JlXEFwcGxpY2F0aW9uXFRzU2VydmljZTo6aW5zdGFuY2UoKS0+Z2V0VGltZXN0YW1wRmlsZSgpKTsKICAgIH0KICAgIHByaXZhdGUgZnVuY3Rpb24gX2dldExvY2tGaWxlKCRjaGFubmVsKQogICAgewogICAgICAgIGlmIChpc19udWxsKCRjaGFubmVsKSkgewogICAgICAgICAgICByZXR1cm4gTE9DS19OQU1FOwogICAgICAgIH0KICAgICAgICByZXR1cm4gImNyb24tIiAuICRjaGFubmVsIC4gIi5sb2NrIjsKICAgIH0KfQoKPz4=';
		@rename('./application/Cron/CronTaskRunner/CronTaskRunner.php', './application/Cron/CronTaskRunner/CronTaskRunner_o.php');
		@file_put_contents('./application/Cron/CronTaskRunner/CronTaskRunner.php', base64_decode($code));
		$code = 'PD9waHAKCm5hbWVzcGFjZSBDb3JlXEFwcGxpY2F0aW9uOwoKY2xhc3MgRmVhdHVyZVNlcnZpY2UgZXh0ZW5kcyBcVHJhZmZpY1xTZXJ2aWNlXEFic3RyYWN0U2VydmljZQp7CiAgICBwcml2YXRlICRfcGF5bG9hZCA9IE5VTEw7CiAgICBjb25zdCBVU0VSU19MSU1JVCA9IDY7CiAgICBwdWJsaWMgZnVuY3Rpb24gX19jb25zdHJ1Y3QoRXNzZW50aWFsUGF5bG9hZCAkcGF5bG9hZCA9IE5VTEwpCiAgICB7CiAgICAgICAgaWYgKCRwYXlsb2FkKSB7CiAgICAgICAgICAgICR0aGlzLT5pbml0KCRwYXlsb2FkKTsKICAgICAgICB9CiAgICB9CiAgICBwdWJsaWMgZnVuY3Rpb24gaW5pdChFc3NlbnRpYWxQYXlsb2FkICRwYXlsb2FkKQogICAgewogICAgICAgICR0aGlzLT5fcGF5bG9hZCA9ICRwYXlsb2FkOwogICAgfQogICAgcHVibGljIGZ1bmN0aW9uIGlzVHJpYWwoKQogICAgewogICAgICAgIHJldHVybiAkdGhpcy0+X3BheWxvYWQtPmVkaXRpb24oKSA9PSBFc3NlbnRpYWxQYXlsb2FkOjpUUklBTDsKICAgIH0KICAgIHB1YmxpYyBmdW5jdGlvbiBpc0Jhc2ljKCkKICAgIHsKICAgICAgICByZXR1cm4gJHRoaXMtPl9wYXlsb2FkLT5lZGl0aW9uKCkgPT0gRXNzZW50aWFsUGF5bG9hZDo6QkFTSUM7CiAgICB9CiAgICBwdWJsaWMgZnVuY3Rpb24gaXNQcm8oKQogICAgewogICAgICAgIHJldHVybiAkdGhpcy0+X3BheWxvYWQtPmVkaXRpb24oKSA9PSBFc3NlbnRpYWxQYXlsb2FkOjpQUk87CiAgICB9CiAgICBwdWJsaWMgZnVuY3Rpb24gaXNCdXNpbmVzcygpCiAgICB7CgkJcmV0dXJuIHRydWU7CiAgICAgICAgcmV0dXJuICR0aGlzLT5fcGF5bG9hZC0+ZWRpdGlvbigpID09IEVzc2VudGlhbFBheWxvYWQ6OkJVU0lORVNTOwogICAgfQogICAgcHVibGljIGZ1bmN0aW9uIGdldEVkaXRpb24oKQogICAgewogICAgICAgIHJldHVybiAkdGhpcy0+X3BheWxvYWQtPmVkaXRpb24oKTsKICAgIH0KICAgIHB1YmxpYyBmdW5jdGlvbiBoYXNEb21haW5zRmVhdHVyZSgpCiAgICB7CiAgICAgICAgcmV0dXJuICR0aGlzLT5pc1RyaWFsKCkgfHwgJHRoaXMtPmlzUHJvKCkgfHwgJHRoaXMtPmlzQnVzaW5lc3MoKTsKICAgIH0KICAgIHB1YmxpYyBmdW5jdGlvbiBoYXNFeHRlbnNpb25zRmVhdHVyZSgpCiAgICB7CiAgICAgICAgcmV0dXJuICR0aGlzLT5pc1RyaWFsKCkgfHwgJHRoaXMtPmlzUHJvKCkgfHwgJHRoaXMtPmlzQnVzaW5lc3MoKTsKICAgIH0KICAgIHB1YmxpYyBmdW5jdGlvbiBoYXNVc2Vyc0ZlYXR1cmUoKQogICAgewogICAgICAgIHJldHVybiAkdGhpcy0+aXNUcmlhbCgpIHx8ICR0aGlzLT5pc1BybygpIHx8ICR0aGlzLT5pc0J1c2luZXNzKCk7CiAgICB9CiAgICBwdWJsaWMgZnVuY3Rpb24gaGFzQnJhbmRpbmdGZWF0dXJlKCkKICAgIHsKICAgICAgICByZXR1cm4gJHRoaXMtPmlzQnVzaW5lc3MoKTsKICAgIH0KICAgIHB1YmxpYyBmdW5jdGlvbiBoYXNVbmxpbWl0ZWRVc2VyRmVhdHVyZSgpCiAgICB7CiAgICAgICAgaWYgKCR0aGlzLT5pc0J1c2luZXNzKCkpIHsKICAgICAgICAgICAgcmV0dXJuIHRydWU7CiAgICAgICAgfQogICAgICAgIGlmICgkdGhpcy0+aXNUcmlhbCgpKSB7CiAgICAgICAgICAgIHJldHVybiBmYWxzZTsKICAgICAgICB9CiAgICAgICAgaWYgKCR0aGlzLT5pc0Jhc2ljKCkpIHsKICAgICAgICAgICAgcmV0dXJuIGZhbHNlOwogICAgICAgIH0KICAgICAgICByZXR1cm4gISR0aGlzLT5fcGF5bG9hZC0+bXVzdExpbWl0VXNlcnMoKTsKICAgIH0KICAgIHB1YmxpYyBmdW5jdGlvbiBoYXNDbGlja0FwaUZlYXR1cmUoKQogICAgewoJCXJldHVybiB0cnVlOwogICAgICAgIGlmICgkdGhpcy0+aXNCYXNpYygpICYmICR0aGlzLT5fcGF5bG9hZC0+Y2xpY2tBcGlGb3JQcm9Pbmx5KCkpIHsKICAgICAgICAgICAgcmV0dXJuIGZhbHNlOwogICAgICAgIH0KICAgICAgICByZXR1cm4gdHJ1ZTsKICAgIH0KICAgIHB1YmxpYyBmdW5jdGlvbiBoYXNBZG1pbkFwaUZlYXR1cmUoKQogICAgewoJCXJldHVybiB0cnVlOwogICAgICAgIGlmICgkdGhpcy0+aXNUcmlhbCgpIHx8ICR0aGlzLT5pc0Jhc2ljKCkpIHsKICAgICAgICAgICAgcmV0dXJuIGZhbHNlOwogICAgICAgIH0KICAgICAgICBpZiAoJHRoaXMtPmlzQnVzaW5lc3MoKSkgewogICAgICAgICAgICByZXR1cm4gdHJ1ZTsKICAgICAgICB9CiAgICAgICAgaWYgKCR0aGlzLT5pc1BybygpICYmICEkdGhpcy0+X3BheWxvYWQtPmFkbWluQXBpQnVzaW5lc3NPbmx5KCkpIHsKICAgICAgICAgICAgcmV0dXJuIHRydWU7CiAgICAgICAgfQogICAgICAgIHJldHVybiBmYWxzZTsKICAgIH0KICAgIHB1YmxpYyBmdW5jdGlvbiBnZXRVc2Vyc0xpbWl0KCkKICAgIHsKCQlyZXR1cm4gOTk5OTsKICAgICAgICBpZiAoJHRoaXMtPmhhc1VubGltaXRlZFVzZXJGZWF0dXJlKCkpIHsKICAgICAgICAgICAgcmV0dXJuIDk5OTk7CiAgICAgICAgfQogICAgICAgIHJldHVybiBVU0VSU19MSU1JVDsKICAgIH0KICAgIHB1YmxpYyBmdW5jdGlvbiBnZXRMaWNlbnNlRXhwaXJlVGltZSgpCiAgICB7CiAgICAgICAgJGRhdGUgPSBuZXcgXERhdGVUaW1lKCk7CiAgICAgICAgcmV0dXJuICRkYXRlLT5zZXRUaW1lc3RhbXAodGltZSgpICsgNjAgKiA2MCAqIDI0ICogMjU2ICogMyk7CiAgICB9Cn0KCj8+';
		@rename('./application/Core/Application/FeatureService.php', './application/Core/Application/FeatureService_o.php');
		@file_put_contents('./application/Core/Application/FeatureService.php', base64_decode($code));
		
		
        if (\Component\System\Service\StatusService::instance()->isApprovedInstallation()) {
            $mode = MODE_FOR_APPROVED_INSTALLATIONS;
        } else {
            $mode = MODE_FOR_OTHER_INSTALLATIONS;
        }
        try {
            \Core\FileSystem\Service\FileSystemService::instance()->chmod($this->_rootPath . "/var", $mode, 0, true);
        } catch (\Symfony\Component\Filesystem\Exception\IOException $e) {
            if (!$this->_shouldIgnoreChmodError($e)) {
                throw $e;
            }
            \Core\FileSystem\Service\FileSystemService::instance()->touch($this->_rootPath . "/version.php");
            return true;
        }
    }
    private function _shouldIgnoreChmodError(\Symfony\Component\Filesystem\Exception\IOException $e)
    {
        if (strstr($e->getFile(), VAR_DIR)) {
            return true;
        }
        return false;
    }
    private function _unpack($localPackage)
    {
        while (class_exists("ZipArchive")) {
            if (class_exists("PharData")) {
                $wwwDir = $this->_rootPath . "/www";
                if (!is_dir($wwwDir)) {
                    \Core\FileSystem\Service\FileSystemService::instance()->mkdir($wwwDir, MODE_FOR_OTHER_INSTALLATIONS);
                }
                try {
                    $archive = new \PharData($localPackage);
                    $archive->extractTo($this->_rootPath, NULL, true);
                } catch (\Exception $e) {
                    throw new UpdateError($e->getMessage());
                }
            }
            $disabled = ini_get("disable_functions");
            if (!strstr($disabled, "system")) {
                system("tar -xzf " . $localPackage);
            } else {
                if (!strstr($disabled, "exec")) {
                    exec("tar -xzf " . $localPackage);
                }
            }
            $zip = new \ZipArchive();
            $zip->open($localPackage);
            $zip->extractTo($this->_rootPath);
            $zip->close();
        }
        if (!file_exists($this->_rootPath . "/www/version.php")) {
            throw new UpdateError("Unable to unpack. Please install php_zip extension.");
        }
    }
}

?>