<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Condition main class.
 *
 * @package availability_sectioncompleted
 * @copyright 2016 iShine Professional College (www.ishinekk.co.jp)
 * @author Justin Hunt {poodllsupport@gmail.com}
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_sectioncompleted;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/completionlib.php');

/**
 * Condition main class.
 *
 * @package availability_sectioncompleted
 * @copyright 2016 iShine Professional College (www.ishinekk.co.jp)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {

    /** @var int the sectionnumber that this depends on */
    protected $sectionnumber;
    const ALL_SECTIONS=-1;
    
    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data.
     */
    public function __construct($structure) {
        // Get sectionnumber
        if (isset($structure->sectionnumber) && is_number($structure->sectionnumber)) {
            $this->sectionnumber = (int)$structure->sectionnumber;
        } else {
            throw new \coding_exception('Missing or invalid ->sectionnumber for completion condition');
        }
    }

    /**
     * Saves tree data back to a structure object.
     *
     * @return \stdClass Structure object (ready to be made into JSON format)
     */
    public function save() {
       return (object)array('type' => 'sectioncompleted',
                'sectionnumber' => $this->sectionnumber);
    }
    
     /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param int $cmid Course-module id of other activity
     * @param int $expectedcompletion Expected completion value (COMPLETION_xx)
     * @return stdClass Object representing condition
     */
    public static function get_json($sectionnumber) {
        return (object)array('type' => 'sectioncompleted', 'sectionnumber' => (int)$sectionnumber);
    }

    /**
     * Determines whether a particular item is currently available
     * according to this availability condition.
     *
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @param bool $grabthelot Performance hint: if true, caches information
     *   required for all course-modules, to make the front page and similar
     *   pages work more quickly (works only for current user)
     * @param int $userid User ID to check availability for
     * @return bool True if available
     */
    public function is_available($not, \core_availability\info $info, $grabthelot=false, $userid=0) {
        global $DB;

        $course = $info->get_course();
        $modinfo = $info->get_modinfo();

        $sec_compl_info = self::get_section_completion_info($this->sectionnumber,$course,$modinfo,$grabthelot,$userid);
        $allow = $sec_compl_info->sectioncompletedinfo>0;

        if ($not) {
            $allow = !$allow;
        }
        return $allow;
    }

    /**
     * Determines whether a particular item is currently available
     * according to this availability condition.
     *
     * @param int $sectionumber either ALL_SECTIONS or a section number
     * @param info $info
     * @param bool $grabthelot Performance hint: if true, caches information
     *   required for all course-modules, to make the front page and similar
     *   pages work more quickly (works only for current user)
     * @param int $userid User ID to check availability for
     * @return bool True if available
     */
    public static function get_section_completion_info($sectionnumber,$course,$modinfo, $grabthelot=false, $userid=0) {

        //It would be easier to use a big fat SQL here but
        //that would be bad from a scalability point of view


        $completioninfo = new \completion_info($course);
        $activitycount = 0; //total activities in total sections searched
        $activitycompletedcount=0; //total completions in total sections searched
        $sectioncount=0; //total sections
        $sectioncompletedcount=0; // total completed sections


        //get the course module and section info we needd
        $sectionnumber = $sectionnumber;
        //get_sections returns an array of sections that each containt an array of cmids
        $sections = $modinfo->get_sections();

        //we try to loop through all or one section, depending on the section number
        if ($sectionnumber==self::ALL_SECTIONS ){
            $searchstart=1;
            $searchend=count($sections);
        }else{
            $searchstart=$sectionnumber;
            $searchend=$sectionnumber;
        }

        //loop through all
        for($thesection=$searchstart;$thesection<$searchend+1;$thesection++) {
            if (array_key_exists($thesection, $sections)) {

                //bump the section count
                $sectioncount++;
                //init count of completable activities in section
                $section_activitycount = 0;
                //init count of completed activities in section
                $section_activitycompletedcount = 0;

                //fetch all activity cmids in section
                $section_cms = $sections[$thesection];
                //loop through all cmids and check their completion status
                foreach ($section_cms as $cmid) {
                    $cm = $modinfo->get_cm($cmid);
                    if ($cm->completion != COMPLETION_TRACKING_NONE) {
                        $section_activitycount++;

                        $data = $completioninfo->get_data($cm, $grabthelot, $userid);
                        if ($data && $data->completionstate > 0) {
                            $section_activitycompletedcount++;
                        }
                    }
                }
                //if the section is completable and complete, bump the completion count
                if ($section_activitycompletedcount > 0 && $section_activitycompletedcount == $section_activitycount) {
                    $sectioncompletedcount++;
                }
                //add section details to the total completeable and completed
                $activitycount += $section_activitycount;
                $activitycompletedcount += $section_activitycompletedcount;
            } //end of if array_key_exists
        }//end of for search sections

        //prepare return object
        $ret = new \stdClass();
        $ret->activitycount = $activitycount; //total activities in total sections searched
        $ret->activitycompletedcount=$activitycompletedcount; //total completions in total sections searched
        $ret->sectioncount=$sectioncount; //total sections
        $ret->sectioncompletedcount=$sectioncompletedcount; //total completed sections
        return $ret;
    }

    /**
     * Obtains a string describing this restriction (whether or not
     * it actually applies). Used to obtain information that is displayed to
     * students if the activity is not available to them, and for staff to see
     * what conditions are.
     *
     * @param bool $full Set true if this is the 'full information' view
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @return string Information string (for admin) about all restrictions on
     *   this item
     */
    public function get_description($full, $not, \core_availability\info $info) {
        // Get number for section.
        $modinfo = $info->get_modinfo();
        $sectionnumber = $this->sectionnumber;
        if (!array_key_exists($this->sectionnumber, $modinfo->get_sections())) {
            $sectionnumber = get_string('missing', 'availability_sectioncompleted');
        }
        if ($not) {
            return get_string('getdescriptionnot', 'availability_sectioncompleted', $sectionnumber);
        }else{
        	return get_string('getdescription', 'availability_sectioncompleted', $sectionnumber);
        }
    }

    /**
     * Obtains a representation of the options of this condition as a string,
     * for debugging.
     *
     * @return string Text representation of parameters
     */
    protected function get_debug_string() {
        return $this->sectioncompleted ? '#' . 'True' : 'False';
    }
}