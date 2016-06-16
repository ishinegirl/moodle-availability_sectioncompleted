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
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        global $DB;
        
        $course = $info->get_course();
        
        
        //It would be easier to use a big fat SQL here but
        //that would be bad from a scalability point of view
        
        $completioninfo = new \completion_info($course);
        //allow until we found an incomplete activity in the section
        $allow =true;
        
        //get the course module and section info we needd
        $mod_info = $info->get_modinfo();
        $sectionnumber =$this->sectionnumber;
		//get_sections returns an array of sections that each containt an array of cmids
        $sections = $mod_info->get_sections();
        $section_cms =$sections[$sectionnumber];
        
        
        foreach($section_cms as $cmid){
        	$cm = $mod_info->get_cm($cmid);
			if($cm->completion != COMPLETION_TRACKING_NONE){
				$data = $completioninfo->get_data($cm);
				if(!$data || $data->completionstate==0){
					$allow =false;
					break;
				}
			}
        }
   
        if ($not) {
            $allow = !$allow;
        }
        return $allow;
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