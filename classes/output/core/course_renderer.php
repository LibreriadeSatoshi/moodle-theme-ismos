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

namespace theme_ismos\output\core;

/**
 * Course listing renderer: every course in a listing is an ISMOS card.
 *
 * @package   theme_ismos
 * @copyright 2026 ISMOS
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_renderer extends \core_course_renderer {
    /**
     * Render one course of a listing as a card.
     *
     * Keeps the .coursebox wrapper and data attributes core scripts and behat look for.
     *
     * Deliberate departure from core: every display mode (collapsed, auto, expanded) renders the same
     * full card. The collapsed "more info" toggle and the enrolment icons are dropped, because a card
     * is a single link with no nested interactive elements, and the card already carries the summary,
     * teachers and fee. The category expander only listens on .coursebox .moreinfo, which is absent, so
     * it keeps working for categories and simply never toggles a course.
     *
     * @param \coursecat_helper $chelper
     * @param \core_course_list_element|\stdClass $course
     * @param string $additionalclasses
     * @return string
     */
    protected function coursecat_coursebox(\coursecat_helper $chelper, $course, $additionalclasses = '') {
        if ($chelper->get_show_courses() <= self::COURSECAT_SHOW_COURSES_COUNT) {
            return '';
        }
        if ($course instanceof \stdClass) {
            $course = new \core_course_list_element($course);
        }
        $classes = trim('coursebox ismos-coursebox ' . $additionalclasses);
        return \html_writer::tag(
            'div',
            $this->output->render_from_template('theme_ismos/course_card', \theme_ismos\local\card::export($course)),
            ['class' => $classes, 'data-courseid' => $course->id, 'data-type' => self::COURSECAT_TYPE_COURSE]
        );
    }

    /**
     * Public course information page (/course/info.php): the whole summary, every contact and an
     * uncropped cover. Any other page type (the enrolment page, for one) keeps core's box.
     *
     * @param \stdClass $course
     * @return string
     */
    public function course_info_box(\stdClass $course) {
        if ($this->page->pagetype !== 'course-info') {
            return parent::course_info_box($course);
        }
        $element = new \core_course_list_element($course);
        $chelper = new \coursecat_helper();
        $context = \context_course::instance($course->id);
        $category = \core_course_category::get($course->category, IGNORE_MISSING, true);
        $fullname = $chelper->get_course_formatted_name($element);
        $dateformat = get_string('strftimedaydate', 'core_langconfig');

        $image = null;
        foreach ($element->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image() && ($info = $file->get_imageinfo())) {
                $image = [
                    'url' => \moodle_url::make_pluginfile_url(
                        $file->get_contextid(),
                        $file->get_component(),
                        $file->get_filearea(),
                        null,
                        $file->get_filepath(),
                        $file->get_filename()
                    )->out(false),
                    'width' => $info['width'],
                    'height' => $info['height'],
                ];
                break;
            }
        }

        $customfields = '';
        if ($element->has_custom_fields()) {
            $handler = \core_course\customfield\course_handler::create();
            $customfields = $handler->display_custom_fields_data($element->get_custom_fields());
        }
        $teachers = \theme_ismos\local\card::teachers($element, 50);

        return $this->render_from_template('theme_ismos/course_info', [
            'title' => $fullname,
            'hidden' => !$course->visible,
            'hiddenlabel' => get_string('hiddenfromstudents'),
            'label' => $category ? format_string($category->name, true, ['context' => $category->get_context(), 'escape' => false]) : '',
            'image' => $image,
            'imagealt' => html_entity_decode(strip_tags($fullname), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'summary' => $chelper->get_course_formatted_summary($element, ['overflowdiv' => false, 'noclean' => false]),
            'hasteachers' => !empty($teachers),
            'teachers' => $teachers,
            'hasdates' => $course->startdate || $course->enddate,
            'startdate' => $course->startdate ? userdate($course->startdate, $dateformat) : '',
            'enddate' => $course->enddate ? userdate($course->enddate, $dateformat) : '',
            'customfields' => $customfields,
            'courseurl' => (new \moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
        ]);
    }

    /**
     * Front page part: every part gets a Plex Mono label and an h2 (the hero is the h1).
     *
     * The available courses part is headed "Choose where to start"; the category tree takes the
     * "Course categories" heading; any other part keeps the heading core gives it. The skip link stays.
     *
     * @param string $skipdivid
     * @param string $contentsdivid
     * @param string $header
     * @param string $contents
     * @return string
     */
    protected function frontpage_part($skipdivid, $contentsdivid, $header, $contents) {
        if (strval($contents) === '') {
            return '';
        }
        $label = get_string('courses', 'theme_ismos');
        if ($contentsdivid === 'frontpage-available-course-list') {
            $header = get_string('coursesheading', 'theme_ismos');
        } else if ($contentsdivid === 'frontpage-category-combo') {
            $header = get_string('categories');
        } else if ($contentsdivid === 'site-news-forum') {
            $label = get_string('sitenews', 'theme_ismos');
        }
        $output = \html_writer::link(
            '#' . $skipdivid,
            get_string('skipa', 'access', \core_text::strtolower(strip_tags($header))),
            ['class' => 'skip-block skip aabtn']
        );
        $output .= \html_writer::start_tag('div', ['id' => $contentsdivid, 'class' => 'ismos-frontpage-part']);
        $output .= \html_writer::span($label, 'ismos-label d-block');
        $output .= $this->output->heading($header);
        $output .= $contents;
        $output .= \html_writer::end_tag('div');
        $output .= \html_writer::tag('span', '', ['class' => 'skip-block-to', 'id' => $skipdivid]);
        return $output;
    }

    /**
     * Course search form; on the front page it is a part like the others, with a label and an h2.
     *
     * @param string $value
     * @return string
     */
    public function course_search_form($value = '') {
        $form = parent::course_search_form($value);
        if ($this->page->pagetype !== 'site-index') {
            return $form;
        }
        return \html_writer::div(
            \html_writer::span(get_string('courses', 'theme_ismos'), 'ismos-label d-block') .
            $this->output->heading(get_string('searchcourses')) . $form,
            'ismos-frontpage-part ismos-frontpage-search'
        );
    }
}
