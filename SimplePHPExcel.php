<?php

require 'lib/PHPExcel.php';

class SimplePHPExcel {

	public $excel;

	/* 行轴起始位置 */
	protected $startX = 2;
	/* 列轴起始位置 */
	protected $startY = 2;
	/* 总行数 */
	protected $totalRows;
	/* 总列数 */
	protected $totalColumns;

	function SimplePHPExcel() {
		$this->excel = new PHPExcel();
	}

	/**
	 * 数组格式
	 * array(
	 * 	'u' => 'Creator',
	 *  'm' => 'LastModifiedBy',
	 *  't' => 'Title',
	 *  's' => 'Subject',
	 *  'd' => 'Description',
	 *  'k' => 'Keywords',
	 *  'c' => 'Category'
	 * )
	 *
	 * @param array $properties
	 */
	function setProperties(array $properties = array()) {
		if(!empty($properties)) {
			$this->excel->getProperties()->setCreator($properties['u'])
								   ->setLastModifiedBy($properties['m'])
								   ->setTitle($properties['t'])
								   ->setSubject($properties['s'])
								   ->setDescription($properties['d'])
								   ->setKeywords($properties['k'])
								   ->setCategory($properties['c']);
		}
	}

	function extensionMapping($extension) {
		$mapping = array(
			'xls'  => 'Excel5',
			'xlsx' => 'Excel2007',
			'html' => 'HTML',
		);
		if( !array_key_exists($extension, $mapping) ) {
			exit('not support extension.');
		}
		return $mapping[$extension];
	}

	/**
	 *
	 * 导出到文件
	 *
	 * @param string $file
	 */
	function exportToFile($file) {
		$extension = $this->getFileExtension($file);
		$type = $this->extensionMapping($extension);
		$writer = PHPExcel_IOFactory::createWriter($this->excel, $type);
		if( empty($file) ) {
			exit('you must give file.');
		}
		$writer->save($file);
	}

	function setDownloadHeader($contentType = null, $fileName) {

		if( is_null($contentType) ) {
			$contentType = 'application/octet-stream';
		}
		header('Content-Type: ' . $contentType);

		//处理中文文件名
		$ua = $_SERVER['HTTP_USER_AGENT'];
		$encodedFileName = urlencode($fileName);
		$encodedFileName = str_replace("+", "%20", $encodedFileName);
		if (preg_match("/MSIE/", $ua)) {
			header('Content-Disposition: attachment; filename="' . $encodedFileName . '"');
		} else if (preg_match("/Firefox/", $ua)) {
			header('Content-Disposition: attachment; filename*="utf8\'\'' . $fileName . '"');
		} else {
			header('Content-Disposition: attachment; filename="' . $fileName . '"');
		}
		header('Cache-Control: max-age=0');

		ob_clean();
	}

	/**
	 * 导出到浏览器
	 *
	 * @param string $fileName
	 */
	function exportToBrowser($fileName) {

		$extension = $this->getFileExtension($fileName);

		switch ($extension) {
			case 'xls':
				$contentType = 'application/vnd.ms-excel';
				break;
			case 'html':
				$contentType = 'text/html';
				break;
			default: 
				/* Excel2007和CSV默认使用同一种ContentType */
				$contentType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
				break;
		}

		$this->setDownloadHeader($contentType, $fileName);

		$type = $this->extensionMapping($extension);
		$objWriter = PHPExcel_IOFactory::createWriter($this->excel, $type);
		$objWriter->save('php://output');
	}

	/**
	 * 添加行
	 * 默认最大支持52列（含起始空白列）
	 *
	 * @param array $row
	 * @param integer $y
	 */
	function addRow(array $row, $y = 0) {
		$x = $this->startX + 65;
		$y = $y ? $y : $this->startY;
		foreach ($row as $item) {
			if($x <= 90) {
				$position = chr($x) . $y;
			}elseif($x > 90 && $x <= 126) {
				$position = "A" . chr($x - 26) . $y;
			}else {
				exit;
			}
			$this->excel->setActiveSheetIndex(0)
				 ->setCellValue($position, $item);
			$x++;
		}
	}

	/**
	 * 绘制表格
	 *
	 * @param array $titles
	 * @param array $bodys
	 */
	function drawTable(array $titles, array $bodys) {
		$this->totalRows = count($bodys);
		$this->totalColumns = count($titles);

		/* 添加头数据 */
		$this->addRow($titles, $this->startY + 1);

		/* 添加行数据 */
		$rowIndex = $this->startY + 2;
		foreach ($bodys as $row) {
			$this->addRow($row, $rowIndex++);
		}

		$styleHeader = array(
			'font' => array(
				'bold' => true,
			),
			'alignment' => array(
				'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
				'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
			),
			'borders' => array(
				'bottom' => array('style' => PHPExcel_Style_Border::BORDER_MEDIUM)
			),
		);
		$startCell = chr($this->startX + 65) . ($this->startY + 1);
		
		$lastCellIndex = $this->startX + 64 + $this->totalColumns;
		$lastCell = chr($lastCellIndex);
		if($lastCellIndex > 90 && $lastCellIndex <= 126) {
			$lastCell = "A" . chr($lastCellIndex - 26);
		}
		$headerColumn = "{$startCell}:{$lastCell}" . ($this->startY + 1);
		$this->excel->getActiveSheet()->getStyle($headerColumn)->applyFromArray($styleHeader);

		$styleOutline = array(
			'borders' => array(
				'outline' => array(
					'style' => PHPExcel_Style_Border::BORDER_MEDIUM,
				)
			)
		);
		$bodyColumn =  "{$startCell}:{$lastCell}" . ($this->totalRows + $this->startY + 1);
		$this->excel->getActiveSheet()->getStyle($bodyColumn)->applyFromArray($styleOutline);
	}

	/**
	 * 设置左侧空白列及顶部空白行
	 *
	 * @param integer $left
	 * @param integer $top
	 */
	function setBlank($left, $top) {
		$this->startX = $left;
		$this->startY = $top;
	}

	/**
	 * 设置列样式
	 *
	 * @param array $styles
	 */
	function setCellStyle($styles) {
		$alignments = array(
			"left" => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
			"right" => PHPExcel_Style_Alignment::HORIZONTAL_RIGHT,
			"center" => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
		);
		foreach ($styles as $k => $v) {
			$cells = $this->getCells($k);
			if(isset($v["bold"])) {
				$this->excel->getActiveSheet()->getStyle($cells)->getFont()->setBold($v["bold"]);
			}elseif (isset($v["align"])) {
				$this->excel->getActiveSheet()->getStyle($cells)->getAlignment()->setHorizontal($alignments[$v["align"]]);
			}elseif (isset($v["color"])){
				$this->excel->getActiveSheet()->getStyle($cells)->getFont()->getColor()->setARGB($v["color"]);
			}
		}
	}

	/**
	 * 获得单元格样式对象
	 *
	 * @param integer|string $colIndex
	 * @return string
	 */
	private function getCells($colIndex) {
		$col = $colIndex;
		/* 列索引的话转换为英文字符 */
		if(is_numeric($colIndex)) {
			$col = chr($colIndex + $this->startX + 64);
		}
		$startCell = $col . ($this->startY + 2);
		$lastCell = $col . ($this->totalRows + $this->startY + 1);
		return "{$startCell}:{$lastCell}";
	}

	/**
	 * 设置列宽
	 * 格式 B=20,C=30,D=20
	 *
	 * @param string $widthStr
	 */
	function setColumnWidthManual($widthStr) {
		$widths = explode(",", $widthStr);
		foreach ($widths as $width) {
			list($colName, $value) = explode("=", $width);
			$col = chr($colName + $this->startX + 64);
			$this->excel->getActiveSheet()->getColumnDimension($col)->setWidth($value);
		}
	}

	/**
	 * 列自适应宽度
	 * 此方法不好用，建议手动设置列宽
	 * 或者配合自动换行使用
	 *
	 */
	function setColumnWidthAuto() {
		$colIndex = $this->startX + 65;
		for ($i = 0; $i < $this->totalColumns; $i++) {
			$this->excel->getActiveSheet()->getColumnDimension(chr($colIndex++))->setAutoSize(true);
		}
//		/* 自动换行 */
//		$this->excel->getActiveSheet()->getStyle("B2:$lastIndex")->getAlignment()->setWrapText(true);
	}


	/**
	 * 获取文件扩展名
	 *
	 * @param string $file
	 * @return string
	 */
	function getFileExtension($file) {
		$extension = pathinfo($file, PATHINFO_EXTENSION);
		$extension = strtolower($extension);
		$supports  = array(
			'xls', 
			'xlsx', 
			'html',
		);
		
		if( !in_array($extension, $supports) ) {
			exit('not support file extension.');
		}
		return $extension;
	}

}

function simple_export_excel($data, $target) {
	$simple = new SimplePHPExcel();

	$simple->setBlank(3, 3);
	$simple->drawTable($data['title'], $data['body']);
	$simple->exportToFile($target);
}

$data = array(
	'title' => array('姓名', '性别', '年龄', '职业'),
	'body'  => array(
		array('张学友', '男', '18', '歌手'),
		array('刘亦菲', '女', '18', '演员'),
		array('何  炅', '男', '18', '主持人'),
		array('陈奕迅', '男', '18', '歌手'),
		array('刘  谦', '男', '18', '魔术师'),
	),
);

simple_export_excel($data, 'UserList.xlsx');

?>
