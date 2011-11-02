<?php

$this->pageTitle=Yii::app()->name; 

$this->breadcrumbs=array(
	'Monthly Report'=>array('/report'),
	'Daily Report for '.$month_str,
);

$this->menu=array(
	array('label'=>'Mailbox Settings', 'url'=>array('mailbox')),
);

?>
<table class="report-daily" border="1" width="100%">
	<thead>
	<caption>Summary for <?php echo CHtml::encode($month_str)?></caption>
		<tr>
			<th>Sunday</th>
			<th>Monday</th>
			<th>Tuesday</th>
			<th>Wednesday</th>
			<th>Thursday</th>
			<th>Friday</th>
			<th>Saturday</th>
			<td>&nbsp;</td>
		</tr>
	</thead>
	<tbody>
		<?php
		$count=count($days);
		$week_arr=array(null,null,null,null,null,null,null);
		$weeks=0;
		$week_count=0;
		for($i=1;$i<=$count;$i++)
		{
			$day=array_shift($days);
			if($day['weekday']==0)
				$week_arr=array(null,null,null,null,null,null,null);
			$week_arr[$day['weekday']]=$day;
			$week_count+=$day['count'];
			if($day['weekday']==6 || $i==$count)
			{
				echo sprintf('<tr class="%s">', (($weeks++ % 2) == 0) ? 'odd' : 'even');
				
				for($j=0;$j<7;$j++)
				{
					$dow=$week_arr[$j];
					if($dow===null)
					{
						echo sprintf('<td>&nbsp;</td>');
					}
					else
					{
						echo sprintf('<td class="nonblank"><span class="report-daily-date">%s</span><span class="report-daily-count">%s mail%s</span></td>',$dow['day'],$dow['count'],$dow['count'] > 1 ? 's' : '');
					}
				}
				echo sprintf('<td>Week #%d<br/>%d mail%s</td>',$weekyear,$week_count,$week_count > 1 ? 's' : '');
				echo '</tr>';
				$weekyear++;
				$week_count=0;
			}
		}
		?>
	</tbody>
	<tfoot>
		<tr>
			<td class="prev_link" colspan="4"><?php if (!empty($prev)): ?><a href="<?php echo $prev['url']?>">&laquo; <?php echo $prev['title']?></a><?php else: ?>&nbsp;<?php endif; ?></td>
			<td class="next_link" colspan="3"><?php if (!empty($next)): ?><a href="<?php echo $next['url']?>"><?php echo $next['title']?> &raquo;</a><?php else: ?>&nbsp;<?php endif; ?></td>
			<td>&nbsp;</td>
		</tr>
	</tfoot>
</table>