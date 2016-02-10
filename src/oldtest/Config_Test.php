<?php
/**
 *  Config_Test.php
 */

/**
 *  Ethna_Configクラスのテストケース
 *
 *  @access public
 */
class Ethna_Config_Test extends Ethna_UnitTestBase
{
    function setUp()
    {
        // etcディレクトリを上書き
        $this->ctl->setDirectory('etc', dirname(__FILE__));
        $this->config = $this->ctl->getConfig();
        $this->filename = dirname(__FILE__) . '/config.php';
    }

    function tearDown()
    {
        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
    }

    function test_getConfigFile()
    {
        $result = $this->config->_getConfigFile();
        $this->assertEqual($result, $this->filename);
    }

    function test_update()
    {
        // この時点ではまだファイルは存在しない
        $result = $this->config->get('foo');
        $this->assertEqual($result, null);

        // Ethna_Configオブジェクト内の値
        $this->config->set('foo', 'bar');
        $result = $this->config->get('foo');
        $this->assertEqual($result, 'bar');

        // ファイルが自動生成される
        $this->config->update();

        // ファイルを読み込み直す
        $this->config->_getConfig();
        $result = $this->config->get('foo');
        $this->assertEqual($result, 'bar');

        // 値を上書き
        $this->config->set('foo', 'baz');
        $this->config->update();

        // もう一度読み込み直す
        $this->config->_getConfig();
        $result = $this->config->get('foo');
        $this->assertEqual($result, 'baz');
    }
}
