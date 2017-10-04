<?php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Created by PhpStorm.
 * User: wang
 * Date: 2017/9/13
 * Time: 14:11
 */

final class AdminController extends BaseController
{
    public function __construct()
    {
        whoop();
        $this->_page = isset($_POST['page']) ? $_POST['page'] : 1;

        $this->_num = isset($_POST['num']) ? $_POST['num'] : 10;

        $this->_metch = "get_admin_{$_POST['a']}";

        $this->cennect_database();

        $log = new Logger('name');
        $log->pushHandler(new StreamHandler('test.log', Logger::INFO));
        $log->addWarning('Foo',['id'=>'123']);
        $log->addError('Bar');
        $log->addInfo('insert',array('id'=>'123'));

    }

    public function __call($methodName, $args)
    {
        json(['code' => 400, 'message' => 'Invalid method : ' . $methodName]);
    }

    /**
     * [get_admin_expand description]
     * parm a:expand
     *      page
     *      num
     * @return [json] [expand of info]
     */
    private function get_admin_expand()
    {
        $where = '';
        $limit = '';
        if (isset($_POST['id'])) {
            $where = "WHERE id={$_POST['id']}";
        } else {
            $sql = "SELECT SQL_CACHE count(*) c FROM rsd_oms_expand ";
            $this->paging($sql);
            $limit = "LIMIT {$this->_limitStart},{$this->_num}";
        }
        $sql = "SELECT name,brName,roleGroupName,ruleName,registerDate,brhLevel FROM rsd_oms_expand {$where} {$limit}";
        $this->out_request($sql);
    }

    /**
     * [get_admin_amt description]
     * parm a:action
     *      page
     *      num
     *      date
     *  flter:
     *      date
     *      id
     * @return [json] [expand of amt]
     */
    private function get_admin_amt()
    {
        //$post = file_get_contents('php://input','r');J($post);
        $this->set_table();

        if (isset($_POST['id'])) {
            $sql = "SELECT expandName,branchName,amt,nums FROM rsd_amt_201708  INNER JOIN rsd_expand_201708  using(expand) WHERE expand={$_POST['id']}";
            /*$stat = $this->_pdo->prepare($sql);
            $id = filter_input(INPUT_POST,'id',FILTER_DEFAULT);
            $stat->bindValue(':id',$id);*/
        } else {
            $sql = "SELECT SQL_CACHE count(*) c FROM {$this->_tableName} ";
            $this->paging($sql);
            $sql = "SELECT e.name,e.brName,amt.amt,amt.nums FROM {$this->_tableName} amt INNER JOIN rsd_oms_expand e WHERE amt.expand=e.id LIMIT {$this->_limitStart},{$this->_num}";
        }
        $this->out_request($sql);
       /* $stat->execute();
        J($stat->fetchAll(2));*/
    }

    /**
     * [get_admin_statistics description]
     * parm a:action
     *      page
     *      num
     *      date
     * flter:date
     *       dateStart
     *       dateEnd
     * @return [json] [statistics for current month]
     */
    private function get_admin_statistics()
    {
        $this->set_table();

        if (isset($_POST['dateStart']) && !empty($_POST['dateStart'])) {
            $this->_outData['dateStart'] = $_POST['dateStart'];
            $this->_outData['dateEnd'] = isset($_POST['dateEnd']) ? $_POST['dateEnd'] : $_POST['dateStart'];

            $where = "WHERE date BETWEEN {$this->_outData['dateStart']} AND {$this->_outData['dateEnd']}";
        } else {
            $where = '';
        }

        $sql = "SELECT SQL_CACHE count(*) c FROM {$this->_tableName} " . $where;
        $this->paging($sql);

        $sql = "SELECT SQL_CACHE sum(amt) allAmt FROM {$this->_tableName} " . $where;
        $sqlData = $this->_pdo->query($sql);
        $this->_outData['allAmt'] = $sqlData->fetch(2)['allAmt'];

        $sql = "SELECT date,time,traceNo,orderNo,name,txName,stat,amt,ibox43,acType FROM {$this->_tableName} {$where} LIMIT {$this->_limitStart},{$this->_num}";
        $this->out_request($sql);
    }

    /**
     * [get_admin_expand_name description]
     * @return [type] [description]
     * parm:
     *     name
     */
    private function get_admin_expand_name()
    {
        $fix = isset($_POST['date']) ? $_POST['date'] : getlastMonth();
        $name = isset($_POST['name']) ? $_POST['name'] : null;

        !is_null($name) || json(['code' => 400, 'message' => 'can`t null!']);
        $table = "rsd_expand_{$fix}";
        $this->set_table($table);

        $sql = "SELECT expand,expandName,branchName FROM {$this->_tableName} WHERE expandName LIKE '{$name}%'";
        $this->out_request($sql);
    }

    public function index()
    {
        $metch = $this->_metch;
        $this->$metch();
    }
}
