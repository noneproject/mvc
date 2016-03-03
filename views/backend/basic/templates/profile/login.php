login page <?fn_echo(self::get('q'));?>

<? if (self::get('users')): ?>
	<table width="50%" border="1">
		<tr>
			<td>Имя</td>
			<td>Телефон</td>
		</tr>
		<? foreach ($users = self::get('users') as $user): ?>
			<tr>
				<td><?=fn_echo($user['name']);?></td>
				<td><?=fn_echo($user['tel']);?></td>
			</tr>
		<? endforeach; ?>
	</table>
<? endif; ?>
