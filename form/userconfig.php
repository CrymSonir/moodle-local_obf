<?php

defined('MOODLE_INTERNAL') or die();

require_once(__DIR__ . '/obfform.php');
require_once(__DIR__ . '/../renderer.php');

class obf_userconfig_form extends local_obf_form_base {

    protected function definition() {
        global $OUTPUT;

        $mform = $this->_form;
        $backpacks = $this->_customdata['backpacks'];
        $userpreferences = $this->_customdata['userpreferences'];

        $mform->addElement('header', 'header_userprefeferences_fields',
                get_string('userpreferences', 'local_obf'));
        $this->setExpanded($mform, 'header_userprefeferences_fields');

        $mform->addElement('advcheckbox', 'badgesonprofile', get_string('showbadgesonmyprofile', 'local_obf'));
        $mform->setDefault('badgesonprofile', $userpreferences->get_preference('badgesonprofile'));

        foreach ($backpacks as $backpack) {
            $this->render_backpack_settings($mform, $backpack);
        }


        $buttonarray = array();


        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('savechanges'),
                array('class' => 'savegroups'));


        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
    private function render_backpack_settings(&$mform, obf_backpack $backpack) {
        global $OUTPUT, $USER;
        $langkey = 'backpack' . (!$backpack->is_connected() ? 'dis' : '') . 'connected';
        $provider = $backpack->get_provider();
        $groupprefix = $backpack->get_providershortname() . 'backpackgroups';
        if ($provider == obf_backpack::BACKPACK_PROVIDER_MOZILLA) {
            $mform->addElement('header', 'header_backpack_fields',
                    get_string('backpacksettings', 'local_obf'));
            $this->setExpanded($mform, 'header_backpack_fields', false);
        } else if ($provider == obf_backpack::BACKPACK_PROVIDER_OBP) {
            $mform->addElement('header', 'header_obpbackpack_fields',
                    get_string('obpbackpacksettings', 'local_obf'));
            $this->setExpanded($mform, 'header_obpbackpack_fields', false);
        }


        $statustext = html_writer::tag('span', get_string($langkey, 'local_obf'),
                        array('class' => $langkey));

        $mform->addElement('static', 'connectionstatus',
                get_string('connectionstatus', 'local_obf'), $statustext);
        $email = $backpack->get_email();

        $mform->addElement('static', 'backpackemail', get_string('backpackemail', 'local_obf'),
                    empty($email) ? '-' : s($email));

        $mform->addHelpButton('backpackemail', 'backpackemail', 'local_obf');

        if ($backpack->is_connected()) {
            $groups = $backpack->get_groups();

            if (count($groups) === 0) {
                $mform->addElement('static', 'nogroups', get_string('backpackgroups', 'local_obf'),
                        get_string('nobackpackgroups', 'local_obf'));
            } else {
                $checkboxes = array();

                foreach ($groups as $group) {
                    $assertions = $backpack->get_group_assertions($group->groupId);
                    $grouphtml = s($group->name) . $OUTPUT->box($this->render_badge_group($assertions),
                                    'generalbox service obf-userconfig-group');
                    $checkboxes[] = $mform->createElement('checkbox', $group->groupId, '',
                            $grouphtml);
                }

                $mform->addGroup($checkboxes, $groupprefix,
                        get_string('backpackgroups', 'local_obf'), '<br  />', true);
                $mform->addHelpButton($groupprefix, 'backpackgroups', 'local_obf');

                foreach ($backpack->get_group_ids() as $id) {
                    $mform->setDefault($groupprefix . '[' . $id . ']', true);
                }
            }
        }
        if (!$backpack->is_connected() && $backpack->requires_email_verification()) {
            $mform->addElement('button', 'backpack_submitbutton',
                    get_string('connect', 'local_obf', 'Backpack'), array('class' => 'verifyemail', 'data-provider' => $backpack->get_provider()));
        } else if (!$backpack->is_connected() && !$backpack->requires_email_verification()) {
            $externaladdhtml = get_string('backpackemailaddexternal'.$backpack->get_providershortname(), 'local_obf', $USER->email);
            $mform->addElement('html', $OUTPUT->notification($externaladdhtml), 'notifyproblem');
        }

        if ($backpack->is_connected() && $backpack->requires_email_verification()) {
            $mform->addElement('cancel', 'cancelbackpack'.$backpack->get_providershortname(),
                    get_string('disconnect', 'local_obf', 'Backpack'));
        }
    }
    private function render_badge_group(obf_assertion_collection $assertions) {
        global $PAGE;

        $items = array();
        $renderer = $PAGE->get_renderer('local_obf');
        $size = -1;

        for ($i = 0; $i < count($assertions); $i++) {
            $assertion = $assertions->get_assertion($i);
            $badge = $assertion->get_badge();
            $items[] = local_obf_html::div($renderer->render_single_simple_assertion($assertion, false) );
        }

        return html_writer::alist($items, array('class' => 'badgelist'));
    }

}
