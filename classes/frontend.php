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
 * Front-end class.
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
 * Front-end class.
 *
 * @package availability_sectioncompleted
 * @copyright 2016 iShine Professional College (www.ishinekk.co.jp)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {

   /**
     * @var array Cached init parameters
     */
    protected $cacheparams = array();

    /**
     * @var string IDs of course, cm, and section for cache (if any)
     */
    protected $cachekey = '';

 	protected function get_javascript_strings() {
        return array('title','label_section');
    }


    protected function get_javascript_init_params($course, \cm_info $cm = null,
            \section_info $section = null) {
    
            // Get list of activities on course which have completion values,
            // to fill the dropdown.
            $context = \context_course::instance($course->id);
            $cms = array();
            $modinfo = get_fast_modinfo($course);
            $dropdowns=array();
			
			$sections = $modinfo->get_sections();
			//print_r($sections);
			//die;
			foreach($sections as $sectionnumber=>$cms){
				foreach($cms as $cmid){
					$cm = $modinfo->get_cm($cmid);
					if($cm->completion != COMPLETION_TRACKING_NONE){
						$dropdowns[] = (object)array('id' => $sectionnumber, 
						'name' =>get_string('sectiontitle','availability_sectioncompleted',$sectionnumber));
						break;
					}
				}
			}
		//	echo 'dropping down';
		//	echo (count($dropdowns));
		//	print_r($dropdowns[19]);
	//		print_r($dropdowns);
        return array($dropdowns);
    }

    protected function allow_add($course, \cm_info $cm = null,
            \section_info $section = null) {
        global $CFG;

        // Check if completion is enabled for the course.
        require_once($CFG->libdir . '/completionlib.php');
        $info = new \completion_info($course);
        if (!$info->is_enabled()) {
            return false;
        }

        // Check if there's at least one other module with completion info.
        $params = $this->get_javascript_init_params($course, $cm, $section);
        $got_params = !empty($params);
        return $got_params;
    }
    
 
}