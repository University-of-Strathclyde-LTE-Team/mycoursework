<?php

class mycoursework_block implements renderable {
    public function __construct($items) {
        $this->items = $items;
    }

}
class mycoursework_list implements renderable {
    /**
     *
     * @param unknown_type $items
     */
    public function __construct($items, $pageurl, $mode = 0, $state = -1) {
        $this->items = $items;
        $this->pageurl = $pageurl;
        $this->renderitemsinstate = $state;
        $this->mode = $mode;
    }

}
class block_mycoursework_renderer extends plugin_renderer_base {

    protected function render_mycoursework_block(mycoursework_block $block) {
        global $OUTPUT;
        $str_overdue = get_string('overdue', 'block_mycoursework');
        $str_duesoon = get_string('duesoon', 'block_mycoursework');
        $str_notdue = get_string('noduedate', 'block_mycoursework');

        $out = '';//print_r($list->items);
        $ordering = array(BLOCK_MYCOURSEWORK_STATUS_OVERDUE,BLOCK_MYCOURSEWORK_STATUS_DUE_SOON);
        foreach($ordering as $order) {
            $items = array();
            foreach($block->items as $item) {
		if ($order == BLOCK_MYCOURSEWORK_STATUS_OVERDUE and $item->submissionstate == 1) {
                     continue;
		}
                if ($item->duestate == $order) {
                        
                    $d = userdate($item->duedate);
                    $text ="<li class=''>{$d} - ";
                    $text .= $item->activityname;
                    $text .= " ({$item->class})";
                    $text .= " ".$item->state;
                    //$text .= "<a href='{$CFG->wwwroot}/mod/{$cm->modname}/view.php?id={$cm->coursemodule}'>";
                    //$text .="{$cm->name}</a> - {$course->fullname}</li>";
                    $items[] =$text;
                }
            }
                
            if (count($items) > 0){
                switch($order) {
                    case BLOCK_MYCOURSEWORK_STATUS_OVERDUE:
                        $out .="<strong><img src='{$OUTPUT->pix_url('redalert', 'block_mycoursework')}'/>{$str_overdue}</strong><ul>";
                        $out .= implode($items);
                        $out .= "</ul>";
                        break;
                    case BLOCK_MYCOURSEWORK_STATUS_DUE_SOON:
                        $out .="<strong><img src='{$OUTPUT->pix_url('LibraryDueNow','block_mycoursework')}'/>{$str_duesoon}</strong><ul>";
                        $out .= implode($items);
                        $out .= "</ul>";
                        break;
                    default:
                    case BLOCK_MYCOURSEWORK_STATUS_NOT_DUE:
                        $out .="<strong><img src='{$OUTPUT->pix_url('green', 'block_mycoursework')}'/>{$str_overdue}</strong><ul>";
                        $out .= implode($items);
                        $out .= "</ul>";
                        break;
                }
            }
        }
        return $out;
    }

    protected function render_mycoursework_list(mycoursework_list $list) {
        global $CFG, $OUTPUT;
        $str_myassignments = get_string('myassignments', 'block_mycoursework');
        $str_mymentees = get_string('mymentees', 'block_mycoursework');
        $str_overdue = get_string('overdue', 'block_mycoursework');
        $str_duesoon = get_string('duesoon', 'block_mycoursework');
        $str_notdue = get_string('noduedate', 'block_mycoursework');
        $str_notduesoon = get_string('notduesoon', 'block_mycoursework');
        $str_due = get_string('due', 'block_mycoursework');
        $str_name = get_string('name');
        $str_class = get_string('course');
        $str_grade =get_string('grade');
        $str_graded =get_string('graded', 'block_mycoursework');
        $str_notsubmitted= get_string('notsubmitted', 'block_mycoursework');
        $str_submitted = get_string('submitted', 'block_mycoursework');
        $str_notgraded = get_string('notgraded', 'block_mycoursework');
        $str_notreleased = get_string('notreleased', 'block_mycoursework');
        $str_state = '';
        $str_viewfeedback = get_string('viewfeedback', 'block_mycoursework');
        $str_today_marker = 'Next Assignment';
        $str_status = get_string('status');
        $str_mark = get_string('mark', 'block_mycoursework');
        $str_selectstudent = get_string('selectstudent', 'block_mycoursework');
        $str_stats = get_string('statistics');

        $out = '';//print_r($list->items);
        $columns = array('state', 'name', 'timedue', 'class', 'status');
        $headers = array($str_state, $str_due, $str_class, $str_name, $str_status);

        $table = new flexible_table('block_mycoursework_all_assignments_table');
        $table->define_columns($columns);
        $table->column_class('state', 'state');
        $table->define_headers($headers);
        $table->baseurl = $list->pageurl;
        //new moodle_url('/blocks/mycoursework/view.php', array('mode' => $mode, 'id' => $uid));
        $table->setup();
        ob_start();
        foreach ($list->items as $instance) {
            $skip = false;
            /*             if($instance->duedate !== null && $instance->duedate > 0 && $instance->duedate < $ignorebefore) {
                continue;
            }
            */
            $str_dueness = '';
            $str_image_due = '';
            $c_status = '';
            $c_due = '';//No Deadline Specified';
            if ($instance->duedate != 0) {
                $c_due = userdate($instance->duedate);
            }
                
            $c_name = $instance->activityname;
            $c_class= $instance->class;
            $c_state = $instance->state;
            if ($list->mode == 1 && !empty($instance->pdaviewitemurl))    { //PDA mode
                $c_state = $OUTPUT->action_link($instance->pdaviewitemurl, $instance->state);
                if (!is_null($instance->grade) && $instance->grade->hidden) {
                    $c_state .= ' (Grade not visible to student)';
                }
                
            } else if (!empty($instance->viewitemurl)) {
/*                 if (!is_null($instance->grade) && $instance->grade->hidden) {
                    $c_state = $OUTPUT->action_link($instance->viewitemurl, "View");
                } else {*/
                     $c_state = $OUTPUT->action_link($instance->viewitemurl, $instance->state);
                //}
            }
            if ($instance->submissionstate == BLOCK_MYCOURSEWORK_STATUS_DONE
                    ||
                    $instance->submissionstate == BLOCK_MYCOURSEWORK_SUBMISSION_UNDEFINED)
            {
                //don't display an image or anything

            } else if ($instance->submissionstate == BLOCK_MYCOURSEWORK_SUBMISSION_SUBMITTED) {
                /*$str_dueness = $str_notduesoon;
                 $str_image_due = 'green.png';*/
            } else if (
                    $instance->submissionstate == BLOCK_MYCOURSEWORK_SUBMISSION_NOT_SUBMITTED
                    &&
                    $instance->duestate == BLOCK_MYCOURSEWORK_STATUS_OVERDUE
            ) {
                $str_dueness = $str_overdue;
                $str_image_due = 'redalert.png';
            } else if (
                    $instance->submissionstate == BLOCK_MYCOURSEWORK_SUBMISSION_NOT_SUBMITTED
                    &&
                    $instance->duestate == BLOCK_MYCOURSEWORK_STATUS_DUE_SOON
            ) {
                $str_dueness = $str_duesoon;
                $str_image_due = 'LibraryDueNow.png';
            } else {
            	//we're in an unknown status!
				$str_dueness = "";
                $str_image_due = '';
            }
            //$c_state .=(int)$instance->submissionstate. ':'.(int)$instance->duestate ;//useful for debugging states
            if (!empty($str_dueness)) {
                $c_status = "<img src='{$CFG->wwwroot}/blocks/mycoursework/pix/{$str_image_due}'".
                        "alt='{$str_dueness}' title='{$str_dueness}' style='height:30px;'/>";
            }
                
                
            $data = array($c_status, $c_due, $c_class, $c_name, $c_state);
            $table->add_data($data);
        }
        $table->finish_output();
        $str_table  = ob_get_clean();
        //$out .= $OUTPUT->box("number of rows:".count($table_rows));
        $out .=$str_table;
        return $out;
    }
    
    function render_block_mycoursework_stats(block_mycoursework_stats $stats) {
        $out ='';
                
        $table = new html_table();
        /* $table->
        $table = new flexible_table('block_mycoursework_user_stats');
        $table->columns = array('statistic','value');
        $table->headers = array('statistic','value');
        $table->setup(); */
        
        //ob_start();
        $roundprecision = 1;
        $rounddirection = PHP_ROUND_HALF_UP;
        $table->data[] = array(get_string('stat_percentage_due_work_submitted','block_mycoursework'), round($stats->deadlines_submitted*100,$roundprecision, $rounddirection)."%");
        $table->data[] = array(get_string('stat_percentage_due_work_not_submitted','block_mycoursework'), round($stats->deadlines_notsubmitted*100,$roundprecision, $rounddirection)."%");
        $table->data[] = array(get_string('stat_percentage_all_work_completed','block_mycoursework'), round($stats->activity_completion*100,$roundprecision, $rounddirection)."% (". $stats->user_activity_submitted."/".$stats->user_activity.")");
        if (is_null($stats->user_averagegrade)) {
            $table->data[] = array(get_string('stat_user_grade_avg','block_mycoursework'),get_string('nogradedactivities','block_mycoursework'));
        } else {
            $table->data[] = array(get_string('stat_user_grade_avg','block_mycoursework'), $stats->user_averagegrade."%");
        }
        $table->data[] = array(get_string('stat_activities_due_soon','block_mycoursework'), $stats->user_activity_due_soon);
        
        //$out .= ob_get_clean();
        $out .= html_writer::table($table);
        return $out;
    }

}
