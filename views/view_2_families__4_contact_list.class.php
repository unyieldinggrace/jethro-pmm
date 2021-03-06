<?php
class View_Families__Contact_List extends View
{

	static function getMenuPermissionLevel()
	{
		return PERM_RUNREPORT;
	}

	function processView()
	{
		$GLOBALS['system']->includeDBClass('family');
		$GLOBALS['system']->includeDBClass('person');
		$GLOBALS['system']->includeDBClass('person_group');
	}
	
	function getTitle()
	{
		return _('Contact List');
	}


	function printView()
	{
		if (!empty($_REQUEST['go'])) {
			if (!empty($_REQUEST['groupid'])) {
				?>
				<div class="pull-right">
				<a class="clickable back"><i class="icon-wrench"></i>Adjust configuration</a><br />
				<i class="icon-download"></i> Download as 
				<a href="<?php echo build_url(Array('call' => 'contact_list', 'format' => 'html', 'view' => NULL)); ?>">HTML</a> |
				<a href="<?php echo build_url(Array('call' => 'contact_list', 'format' => 'docx', 'view' => NULL)); ?>">DOCX</a>
				</div>
				<?php
				$this->printResults();
				return;
			} else {
				print_message(_("You must choose an opt-in group"), 'error');
			}
		}
		$this->printForm();
	}

	function printForm()
	{
		$dummy_person = new Person();
		$dummy_person->fields['congregationid']['allow_multiple'] = true;
		$dummy_person->fields['age_bracketid']['allow_multiple'] = true;
		?>
		<form method="get">
		<input type="hidden" name="view" value="<?php echo ents($_REQUEST['view']); ?>" />
		<table>
			<tr>
				<th><?php echo _('Opt-in group');?></th>
				<td>
					<?php echo _('Only show families that have a member in the group'); ?> <br />
					<?php Person_Group::printChooser('groupid', 0); ?></td>
			</tr>
			<tr>
				<th><?php echo _('Congregation');?></th>
				<td><?php echo _('Only include opted-in persons from');?><br />
				<?php $dummy_person->printFieldInterface('congregationid'); ?></td>
			</tr>
			<tr>
				<th><?php echo _('Age brackets');?></th>
				<td><?php echo _('Only show contact details for persons who are');?><br />
				<?php $dummy_person->printFieldInterface('age_bracketid'); ?>
				</td>
			</tr>
			<tr>
				<th><?php echo _('Other family members');?></th>
				<td><?php echo _('When a family has other members not in the opt-in group above:');?><br />
				<label class="radio">
					<input type="radio" name="all_member_details" value="-1" checked="checked" id="all_member_details_0" />
					<?php echo _('Do not show them at all');?>
				</label>
				<label class="radio">
					<input type="radio" name="all_member_details" value="0" checked="checked" id="all_member_details_0" />
					<?php echo _('Show their names but no contact details');?>
				</label>
				<label class="radio">
					<input type="radio" name="all_member_details" value="1" id="all_member_details_1" />
					<?php echo _('Show their contact details just like the opted-in persons');?>
				</label>
			</tr>
			<tr>
					<th><?php echo _('Details to show');?></th>
					<td>
						<label class="checkbox">
							<?php
							print_widget('include_address', Array('type' => 'checkbox'), array_get($_REQUEST, 'include_address', TRUE));
							echo _('Home address');
							?>
						</label>
						<label class="checkbox">
							<?php
							print_widget('include_home_tel', Array('type' => 'checkbox'), array_get($_REQUEST, 'include_home_tel', TRUE));
							echo _('Home phone');
							?>
						</label>
						<label class="checkbox">
							<?php
							print_widget('include_congregation', Array('type' => 'checkbox'), array_get($_REQUEST, 'include_congregation', TRUE));
							echo _('Congregation');
							?>
						</label>
					<?php
					if ($GLOBALS['system']->featureEnabled('PHOTOS')) {
						?>
						<label class="checkbox">
							<?php
							print_widget('include_photos', Array('type' => 'checkbox'), array_get($_REQUEST, 'include_photos', TRUE));
							echo _('Family photos');
							?>
						</label>
						<?php
					}
					?>
					</td>
			</tr>
			<tr>
				<th></th>
				<td>
					<input class="btn" type="submit" name="go" value="Show results" />
				</td>
			</tr>
		</table>
		</form>
		<?php
	}

	function printResults($dataURLs=FALSE)
	{
		?>
		<table class="contact-list">
		<?php
		foreach ($this->getData() as $family) {
			?>
			<tr>
			<?php
			if (!empty($_REQUEST['include_photos'])) {
				$rowSpan = count($family['optins']) + 3;
				if (!empty($_REQUEST['include_home_tel']) && $family['home_tel']) $rowSpan++;
				if (!empty($_REQUEST['include_address']) && $family['address_street']) $rowSpan++;
				if (($family['have_photo']) || count($family['optins']) == 1) {
					if ($dataURLs) {
						$src = Photo_Handler::getDataURL('family', $family['familyid']);
					} else {
						$src = '?call=photo&familyid='.$family['familyid'];
					}
				} else {
					$src = BASE_URL.'resources/img/unknown_family.gif';
				}
				?>
				<td rowspan="<?php echo $rowSpan; ?>" style="padding: 5px">
					<img src="<?php echo $src; ?>" />
				</td>
				<?php
			}
			?>
				<td colspan="4" style="height: 1px">
					<h2 style="margin: 5px 0px 0px 0px"><?php echo $family['family_name']; ?></h2>
				</td>
			</tr>
			<tr style="height: 1px">
				<td colspan="4"><i><?php echo ents($family['all_names']); ?></td>
			</tr>
			<?php
			if (!empty($_REQUEST['include_home_tel']) && $family['home_tel']) {
				echo '<tr style="height: 1px"><td colspan="4">';
				echo ents($family['home_tel']);
				echo '</td></tr>';
			}
			if (!empty($_REQUEST['include_address']) && $family['address_street']) {
				echo '<tr style="height: 1px"><td colspan="4">'.nl2br(ents($family['address_street'])).'<br />';
				echo ents($family['address_suburb'].' '.$family['address_state'].' '.$family['address_postcode']);
				echo '</td></tr>';
			}
			foreach ($family['optins'] as $adult) {
				?>
				<tr style="height: 1px">
					<td style="padding-right: 1ex"><?php echo ents($adult['name']); ?></td>
					<td style="padding-right: 1ex">
						<?php 
						if (!empty($_REQUEST['include_congregation'])) echo ents($adult['congname']); 
						?>
					</td>
					<td style="padding-right: 1ex"><?php echo ents($adult['mobile_tel']); ?></td>
					<td><?php echo ents($adult['email']); ?></td>
				</tr>
				<?php
			}
			if (!empty($_REQUEST['include_photos'])) {
				// to take up extra vertical space
				?>
				<tr>
					<td colspan="4">&nbsp;</td>
				</tr>
				<?php
			}
		}
		?>
		</table>
		<?php
	}

	private function getData()
	{
		$dummy_person = new Person();
		$dummy_family = new Family();

		$db = $GLOBALS['db'];
		$groupid = (int)$_REQUEST['groupid'];
		$all_member_details = array_get($_REQUEST, 'all_member_details', 0);

		if (empty($groupid)) return;

		$sql = '
		select family.id as familyid, family.family_name, family.home_tel,
			person.id, person.first_name, person.last_name, person.mobile_tel, person.email, person.age_bracketid,
			congregation.long_name as congname,
			address_street, address_suburb, address_state, address_postcode,
			IF (fp.familyid IS NULL, 0, 1) as have_photo,
			IF (signup.groupid IS NULL, 0, 1) as signed_up
		from family
		join person on family.id = person.familyid
		join age_bracket ab ON ab.id = person.age_bracketid
		left join congregation on person.congregationid = congregation.id
		left join family_photo fp ON fp.familyid = family.id
		left join person_group_membership signup ON signup.personid = person.id AND signup.groupid = '.(int)$groupid.'
		where person.status <> "archived"
		and family.id in
		(select familyid
		from person join person_group_membership pgm on person.id = pgm.personid
		where pgm.groupid = '.(int)$groupid;

		if (!empty($_REQUEST['congregationid'])) {
			$sql .= '
				AND person.congregationid in ('.implode(',', array_map(Array($db, 'quote'), $_REQUEST['congregationid'])).')';
		}
		$sql .= ')
		order by family_name asc, familyid, ab.rank asc, gender desc
		';
		$res = $db->queryAll($sql, null, null, true, true, true);
		check_db_result($res);

		if (empty($res)) {
			?><p><i><?php echo _('No families to show');?></i></p><?php
			return;
		}

		$GLOBALS['system']->includeDBClass('family');
		$GLOBALS['system']->includeDBClass('person');

		$families = Array();

		foreach ($res as $familyid => $family_members) {
			$family = Array(
						'familyid' => $familyid,
						'optins' => Array(),
				);
			$adults_use_full = FALSE;
			$all_use_full = FALSE;
			foreach ($family_members as $member) {
				$member['name'] = $member['first_name'];
				// show full details if
				// - (they are signed up, or all-member-details is 1)
				// - AND (their age bracket is correct) OR all age brackets are in
				if (
					($member['signed_up'] || $all_member_details == 1)
					&& (empty($_REQUEST['age_bracketid']) || in_array($member['age_bracketid'], $_REQUEST['age_bracketid']))
				) {
					$member['mobile_tel'] = $dummy_person->getFormattedValue('mobile_tel', $member['mobile_tel']);
					$family['optins'][] = $member;
					if ($member['last_name'] != $member['family_name']) {
						$adults_use_full = true;
					}
				}
				
				if ($member['signed_up'] || $all_member_details != -1) {
					$family['all'][] = $member;
				}
				if ($member['last_name'] != $member['family_name']) {
					$all_use_full = true;
				}
			}

			if ($adults_use_full) {
				foreach ($family['optins'] as &$adult) {
					$adult['name'] .= ' '.$adult['last_name'];
				}
				unset($adult);
			}
			if ($all_use_full) {
				foreach ($family['all'] as &$member) {
					$member['name'] .= ' '.$member['last_name'];
				}
				unset($member);
			}

			$family['all_names'] = Array();
			foreach ($family['all'] as $member) {
				$family['all_names'][] = $member['name'];
			}
			$last = '';
			if (count($family['all_names']) > 1) $last = array_pop($family['all_names']);
			$family['all_names'] = implode(', ', $family['all_names']);
			if ($last) $family['all_names'] .= ' & '.$last;

			$first_member = reset($family_members);
			foreach (Array('have_photo', 'family_name', 'home_tel', 'address_street', 'address_suburb', 'address_state', 'address_postcode') as $ffield) {
				$family[$ffield] = $first_member[$ffield];
			}
			$family['home_tel'] = $dummy_family->getFormattedValue('home_tel', $family['home_tel']);

			$families[] = $family;
		}
		return $families;
	}


	public function printDOCX()
	{
		require_once 'include/odf_tools.class.php';
		require_once 'vendor/autoload.php';
		\PhpOffice\PhpWord\Settings::setTempDir(sys_get_temp_dir());
		\PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(TRUE);
		
		require_once 'view_9_documents.class.php';
		$width = 11338; // 20cm in twips
		$phpWord =  new \PhpOffice\PhpWord\PhpWord();
		$phpWord->addParagraphStyle('FAMILY-HEADER', array());
		$phpWord->addParagraphStyle('FAMILY-SUB-HEADER', array());
		$phpWord->addFontStyle('FAMILY-NAME', array('bold' => true, 'size' => 15));
		$phpWord->addFontStyle('FAMILY-MEMBERS', array('italic' => true));
		$phpWord->addFontStyle('HOME-PHONE', array());
		$phpWord->addFontStyle('ADDRESS', array());
		$phpWord->addFontStyle('PERSON-NAME', array('bold' => true));
		$phpWord->addTableStyle('FAMILY-LIST', array('width' => $width, 'borderSize' => 0, 'cellMargin' => 80,'borderColor' => 'CCCCCC'));

		/*$intro = $phpWord->addSection();
		$intro->addTitle(SYSTEM_NAME.' Contact List', 1);
		$intro->addText('Intro text goes here');*/

		$section = $phpWord->addSection(array('breakType' => 'continuous'));

		/*$outro = $phpWord->addSection(array('breakType' => 'continuous'));
		$outro->addText('Concluding text goes here');*/

		

		$table = $section->addTable('FAMILY-LIST');

		$gridspan = 3;
		if (!empty($_REQUEST['include_congregation'])) $gridspan++;
		
		$wideCellProps = array('gridSpan' => $gridspan, 'valign' => 'top');
		$narrowCellProps = array('valign' => 'top');

		foreach ($this->getData() as $family) {
			$table->addRow();
			$table->addCell(NULL, $wideCellProps)
						->addText($family['family_name'], 'FAMILY-NAME', 'FAMILY-HEADER');
						
			$table->addRow();
			$table->addCell(NULL, $wideCellProps)
						->addText($family['all_names'], 'FAMILY-MEMBERS', 'FAMILY-SUB-HEADER');

			if (!empty($_REQUEST['include_address']) && $family['address_street']) {
				$table->addRow();
				$cell = $table->addCell(NULL, $wideCellProps);
				$cell->addText($family['address_street'], 'ADDRESS');
				$cell->addText($family['address_suburb'].' '.$family['address_state'].' '.$family['address_postcode'], 'ADDRESS');
			}

			if (!empty($_REQUEST['include_home_tel']) && $family['home_tel']) {
				$table->addRow();
				$table->addCell(NULL, $wideCellProps)
							->addText($family['home_tel'], 'HOME PHONE');
			}

			foreach ($family['optins'] as $member) {
				$table->addRow();
				$table->addCell($width*0.3, $narrowCellProps)->addText($member['name'], 'PERSON-NAME');
				if (!empty($_REQUEST['include_congregation'])) {
					$table->addCell($width*0.25, $narrowCellProps)->addText($member['congname']);
				}
				$table->addCell($width*0.2, $narrowCellProps)->addText($member['mobile_tel']);
				$table->addCell($width*0.2, $narrowCellProps)->addText($member['email']);
			}
		}

		$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
		$tempname = tempnam(sys_get_temp_dir(), 'contactlist');
		$objWriter->save($tempname);
		
		//readfile($tempname);
		
		$templateFilename = Documents_Manager::getRootPath().'/Templates/contact_list_template.docx';
		if (!file_exists($templateFilename)) {
			$templateFilename = JETHRO_ROOT.'/resources/contact_list_template.docx';
		}
		if (file_exists($templateFilename)) {
			require_once 'include/odf_tools.class.php';
			$outname = tempnam(sys_get_temp_dir(), 'contactlist').'.docx';
			copy($templateFilename, $outname);
			ODF_Tools::insertFileIntoFile($tempname, $outname, '%CONTACT_LIST%');
			$replacements = Array('SYSTEM_NAME' => SYSTEM_NAME);
			ODF_Tools::replaceKeywords($outname, $replacements);
			readfile($outname);
			unlink($outname);
		} else {
			readfile($tempname);
		}
		unlink($tempname);
		 
	}
}
