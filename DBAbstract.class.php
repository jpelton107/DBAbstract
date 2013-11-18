<?php
abstract class DBAbstract
{
	protected $table;
	protected $dbh;
	protected $id;
	public $cache = array();
	public $cols;
	public $pKey;
	protected $count;
	protected $query;
	protected $resultsPerPage=15;
	protected $defaultOrderBy;

	public function __construct($dbh, $id=false) {
		$this->dbh = $dbh;
		if (!$this->table) {
			$this->table = get_class($this);
		}
		$this->cols = !$this->cols ? $dbh->MetaColumnNames($this->table) : $this->cols;
		$this->pKey = $dbh->MetaPrimaryKeys($this->table);
		$this->id = $id;
	}


	public function setID($id) 
	{
		$this->id = $id;
	}

	public function load($vals) 
	{
		foreach($vals as $k => $val) {
			if (array_key_exists($k, $this->cols)) {
				$this->cache[$k] = $val;
			}
		}
	}

	public function update($vals, $updateby=false, $where=false)
	{
		if (!$where) { 
			$key = $this->pKey[0];
			if ($this->cache[$key]) {
				$var = $this->cache[$key];
			} else {
				$var = $this->id;
			}
			$query = "$key = '".$var."'"; 
		} else { 
			foreach($where as $k => $v) {
				$query .= $k . "='".$v."' and ";
			}
			$query = substr($query, 0, -5);
		}

		if ($updateby) {
			if (in_array('UpdateBy', $this->cols)) {
				$vals['UpdateBy'] = $updateby;
			}
		}
		if (in_array('LastUpdate', $this->cols)) {
			$vals['LastUpdate'] = date('Y-m-d H:i:s');
		}

		$this->dbh->AutoExecute($this->table, $vals, 'UPDATE', $query);
		$this->cache = $vals;
	}

	public function insert($vals) 
	{
		$this->dbh->AutoExecute($this->table, $vals, 'INSERT');
		$this->cache = $vals;
		return $this->dbh->Insert_ID();
	}

	public function get($where=false, $orderby=false) {
		$query = "select ";
		foreach($this->cols as $col) {
			$query .= $col . ", ";
		}
		$query = substr($query, 0, -2);

		$query .= " from ".$this->table." where ";
		if (!$where) {
			$where = array($this->pKey[0] => $this->id);
		}
		foreach($where as $k => $a) {
			$query .= " $k=? and ";
			$params[] = $a;
		}
		$query = substr($query, 0, -4);
		if ($orderby) {
			$query .= " order by ".$orderby." desc ";
		}

		$res = $this->dbh->GetRow($query, $params);
		$this->cache = $res;
		return $res;
	}

	public function all($where=false)
	{
		$query = "select *
			from ".$this->table;
		if ($where) {
			$query .= " where ";
			foreach($where as $field => $val) {
				$query .= "$field=? and";
				$params[] = $val;
			}
			$query = substr($query,0,-4);
		}
		return $this->dbh->GetAll($query, $params);
	}

	public function delete($where=false)
	{
		$query = "delete from ".$this->table." where ";
		if (!$where) { 
			$key = $this->pKey[0];
			if ($this->cache[$key]) {
				$query .= "$key = '".$this->cache[$key]."'";
			} else {
				$query .= "$key = '".$this->id."'";
			}
		} else {
			foreach($where as $k => $v) {
				$query .= $k . "='".$v."' and ";
			}
			$query = substr($query, 0, -5);
		}

		$this->dbh->Execute($query);
	}

	public function find($field, $value) {
		$query = "select count(*) 
			    from ".$this->table." 
			   where ".$field."=?
			   ";
		return $this->dbh->GetOne($query, array($value));
	}

	public function getCurrentPageResults($page)
	{
		if (!$page) { $page = 1; }
		$rowStart = $page * $this->resultsPerPage - $this->resultsPerPage;
		$rowEnd = $page * $this->resultsPerPage;
		$ret = array();
		for($i=$rowStart;$i<$rowEnd;$i++) {
			if ($this->cache[$i]) {
				$ret[] = $this->cache[$i];
			}
		}
		return $ret;
	}

	public function getPages() 
	{
		return ceil($this->count / $this->resultsPerPage);
	}

	public function getCount()
	{
		return $this->count;
	}

}

		
