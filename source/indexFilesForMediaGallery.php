<?php
/**
 * This file is part of OXID eSales developer documentation.
 *
 * OXID eSales developer documentation is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * OXID eSales developer documentation is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OXID eSales OXID eShop Facts. If not, see <http://www.gnu.org/licenses/>.
 *
 * @link          http://www.oxid-esales.com
 * @copyright (C) OXID eSales AG 2003-2017
 */

/**
 * Configure the database connection to your OXID eShop 4.10 / 5.3 database
 */
$dbHost = "localhost";
$dbUser = "oxid";
$dbPassword = "oxid";
$dbName = "";


// == LOGIC SECTION ===================================================================

class MediaGalleryIndexer
{

    protected $_sMediaPath = '/out/pictures/ddmedia/';
    protected $_iDefaultThumbnailSize = 185;

    private $databaseConnection = null;

    /**
     * @param string       $sFile
     * @param null|integer $iThumbSize
     *
     * @return bool|string
     */
    public function getThumbnailUrl($sFile = '', $iThumbSize = null)
    {
        if ($sFile) {
            if (!$iThumbSize) {
                $iThumbSize = $this->_iDefaultThumbnailSize;
            }

            $sThumbName = $this->getThumbName($sFile, $iThumbSize);

            if ($sThumbName) {
                return $this->getMediaUrl('thumbs/' . $sThumbName);
            }
        } else {
            return $this->getMediaUrl('thumbs/');
        }

        return false;
    }


    /**
     * @param string       $sFile
     * @param null|integer $iThumbSize
     *
     * @return string
     */
    public function getThumbName($sFile, $iThumbSize = null)
    {
        if (!$iThumbSize) {
            $iThumbSize = $this->_iDefaultThumbnailSize;
        }

        return str_replace('.', '_', md5(basename($sFile))) . '_thumb_' . $iThumbSize . '.jpg';
    }

    /**
     * @return int
     */
    public function getDefaultThumbSize()
    {
        return $this->_iDefaultThumbnailSize;
    }


    /**
     * Create directories
     */
    public function createDirs()
    {
        if (!is_dir($this->getMediaPath())) {
            mkdir($this->getMediaPath());
        }

        if (!is_dir($this->getThumbnailPath())) {
            mkdir($this->getThumbnailPath());
        }
    }

    /**
     * @param string $sFile
     *
     * @return string
     */
    public function getThumbnailPath($sFile = '')
    {
        $sPath = $this->getMediaPath() . 'thumbs/';

        if ($sFile) {
            return $sPath . $sFile;
        }

        return $sPath;
    }

    /**
     * @param string       $sFileName
     * @param null|integer $iThumbSize
     * @param bool         $blCrop
     *
     * @return bool|string
     * @throws Exception
     */
    public function createThumbnail($sFileName, $iThumbSize = null, $blCrop = true)
    {
        $sFilePath = $this->getMediaPath($sFileName);

        if (is_readable($sFilePath)) {
            if (!$iThumbSize) {
                $iThumbSize = $this->_iDefaultThumbnailSize;
            }

            list($iImageWidth, $iImageHeight, $iImageType) = getimagesize($sFilePath);

            switch ($iImageType) {
                case 1:
                    $rImg = imagecreatefromgif($sFilePath);
                    break;

                case 2:
                    $rImg = imagecreatefromjpeg($sFilePath);
                    break;

                case 3:
                    $rImg = imagecreatefrompng($sFilePath);
                    break;

                default:
                    throw new Exception('Invalid filetype');
                    break;
            }

            $iThumbWidth = $iImageWidth;
            $iThumbHeight = $iImageHeight;

            $iThumbX = 0;
            $iThumbY = 0;

            if ($blCrop) {
                if ($iImageWidth < $iImageHeight) {
                    $iThumbWidth = $iThumbSize;
                    $iThumbHeight = $iImageHeight / ($iImageWidth / $iThumbWidth);

                    $iThumbY = (($iThumbSize - $iThumbHeight) / 2);
                } elseif ($iImageHeight < $iImageWidth) {
                    $iThumbHeight = $iThumbSize;
                    $iThumbWidth = $iImageWidth / ($iImageHeight / $iThumbHeight);

                    $iThumbX = (($iThumbSize - $iThumbWidth) / 2);
                }
            } else {
                if ($iImageWidth < $iImageHeight) {
                    if ($iImageHeight > $iThumbSize) {
                        $iThumbWidth *= ($iThumbSize / $iImageHeight);
                        $iThumbHeight *= ($iThumbSize / $iImageHeight);
                    }
                } elseif ($iImageHeight < $iImageWidth) {
                    if ($iImageHeight > $iThumbSize) {
                        $iThumbWidth *= ($iThumbSize / $iImageWidth);
                        $iThumbHeight *= ($iThumbSize / $iImageWidth);
                    }
                }
            }

            $rTmpImg = imagecreatetruecolor($iThumbWidth, $iThumbHeight);
            imagecopyresampled($rTmpImg, $rImg, $iThumbX, $iThumbY, 0, 0, $iThumbWidth, $iThumbHeight, $iImageWidth, $iImageHeight);

            if ($blCrop) {
                $rThumbImg = imagecreatetruecolor($iThumbSize, $iThumbSize);
                imagefill($rThumbImg, 0, 0, imagecolorallocate($rThumbImg, 0, 0, 0));

                imagecopymerge($rThumbImg, $rTmpImg, 0, 0, 0, 0, $iThumbSize, $iThumbSize, 100);
            } else {
                $rThumbImg = $rTmpImg;
            }

            $sThumbName = $this->getThumbName($sFileName, $iThumbSize);

            imagejpeg($rThumbImg, $this->getThumbnailPath($sThumbName));

            return $sThumbName;
        }

        return false;
    }

    /**
     * @param string $sFile
     *
     * @return string
     */
    public function getMediaPath($sFile = '')
    {
        $sPath = rtrim(dirname(__FILE__), '/');

        if ($sFile) {
            return $sPath . $sFile;
        }

        return $sPath;
    }

    /**
     * @param string $sFileName
     */
    public function createMoreThumbnails($sFileName)
    {
        // More Thumbnail Sizes
        $this->createThumbnail($sFileName, 300);
        $this->createThumbnail($sFileName, 800);
    }

    /*
     * @param string $dbHost,
     * @param string $dbUser
     * @param string $dbPassword
     * @param string $dbName
     *
     * @return PDO
     *
     * @throws PDOException
     */
    public function connectToDatabase($dbHost, $dbUser, $dbPassword, $dbName)
    {
        $this->databaseConnection = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPassword);
    }

    /**
     * @return DirectoryIterator
     */
    private function getFileIterator()
    {
        return new RecursiveDirectoryIterator(
            $this->getMediaPath(),
            FilesystemIterator::SKIP_DOTS
        );
    }

    /*
     * @param string $sFileName
     *
     * @return $sThumbName
     */
    private function generateThumbnails($sFileName)
    {
        try {
            $sThumbName = $this->createThumbnail($sFileName);
            $this->createMoreThumbnails($sFileName);
        } catch ( Exception $e) {
            $sThumbName = '';
        }
        return $sThumbName;
    }

    /**
     * @param FileInfo $fileInfo
     *
     * @return string $result the number of rows affected
     *
     * @throws Exception
     */
    private function writeFileToIndexTable($fileInfo, $thumbName)
    {
        $mimeType = $this->getMimeType($fileInfo->getPathName());
        $sqlInsert = "REPLACE INTO `ddmedia`
                          ( `OXID`, 
                          `OXSHOPID`, 
                          `DDFILENAME`, 
                          `DDFILESIZE`, 
                          `DDFILETYPE`, 
                          `DDTHUMB`, 
                          `DDIMAGESIZE` )
                        VALUES
                          ( '" . md5( $fileInfo->getPathName() ) . "', 
                          '" . 1 . "', 
                          '" . $fileInfo->getName() . "', 
                          " . $fileInfo->getSize() . ", 
                          '" . $mimeType . "', 
                          '" . $thumbName . "', 
                          '" . $this->getImageSize($fileInfo->getPathName(), $mimeType) . "' );";

        $numberOfAffectedRows = $this->databaseConnection->exec($sqlInsert);
        if ($numberOfAffectedRows === false) {
            throw Exception('Could not insert File ' . $fileInfo);
        }
        return $numberOfAffectedRows;
    }

    /**
     * @param string $fileName
     *
     * @return string The mimetype of the file
     */
    private function getMimeType($fileName)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileName);
        finfo_close($finfo);
        return $mimeType;
    }

    /**
     * @param string $fileName
     * @param string $mimeType
     *
     * @return string The size of the image
     */
    private function getImageSize($fileName, $mimeType)
    {
        $sImageSize = '';
        if (is_readable($fileName) && preg_match("/image\//", $mimeType)) {
            $aImageSize = getimagesize($fileName);
            $sImageSize = ($aImageSize ? $aImageSize[0] . 'x' . $aImageSize[1] : '');
        }
        return $sImageSize;
    }


    public function index()
    {
        $numberOfAffectedRows = 0;
        $fileIterator = $this->getFileIterator();
        foreach ($fileIterator as $fileInfo) {
            $thumbName = $this->generateThumbnails($fileInfo->getPathname());
            $result = $this->writeFileToIndexTable($fileInfo, $thumbName);
            if ($result !== false) {
                $numberOfAffectedRows += $result;
            }
        }
        echo 'Number of files found: "' . $numberOfAffectedRows . "\n";
    }


}
try {
    $mediaGalleryIndexer = new MediaGalleryIndexer();
    $mediaGalleryIndexer->connectToDatabase($dbHost, $dbUser, $dbPassword, $dbName);
    $mediaGalleryIndexer->index();
} catch (PDOException $pdoException) {
    echo "Please configure your database correctly" . PHP_EOL;
} catch (Exception $exception) {
    echo $exception->getMessage(). PHP_EOL;
}
