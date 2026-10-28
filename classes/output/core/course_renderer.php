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
    /** @var string[] Front page parts the landing page replaces (available courses, category tree). */
    private const LANDING_PARTS = [
        'frontpage-available-course-list',
        'frontpage-category-combo',
        'frontpage-category-names',
    ];

    /** @var bool Whether this request already gave a card cover the eager, high priority load. */
    protected bool $coverprioritised = false;

    /**
     * Render one course of a listing as a card.
     *
     * Keeps the .coursebox wrapper and data attributes core scripts and behat look for.
     *
     * Cover images: the first one rendered gets eager loading and high fetch priority.
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
        $data = \theme_ismos\local\card::export($course);
        if ($data['image'] !== null) {
            // Only the first image of the page is likely above the fold; the rest load lazily.
            $data['image']['prioritise'] = !$this->coverprioritised;
            $this->coverprioritised = true;
        }
        return \html_writer::tag(
            'div',
            $this->output->render_from_template('theme_ismos/course_card', $data),
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

        $image = \theme_ismos\local\card::cover($element);

        $customfields = '';
        if ($element->has_custom_fields()) {
            $handler = \core_course\customfield\course_handler::create();
            $customfields = trim($handler->display_custom_fields_data($element->get_custom_fields()));
        }
        $teachers = \theme_ismos\local\card::teachers($element, null);

        return $this->render_from_template('theme_ismos/course_info', [
            'title' => $fullname,
            'hidden' => !$course->visible,
            'hiddenlabel' => get_string('hiddenfromstudents'),
            'label' => $category ? format_string($category->name, true, ['context' => $category->get_context(), 'escape' => false]) : '',
            'image' => $image,
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
     * Enrolment page. When the course's only way in is manual enrolment, the student can't do anything on the page:
     * instead of core's "You cannot enrol yourself in this course", they get the address to write to (the theme's
     * enrolment contact setting), with the course name as the subject. Any other case keeps core's output.
     *
     * @param \stdClass $course
     * @param string[] $widgets the enrolment methods' forms
     * @param \core\url|null $returnurl
     * @return string
     */
    public function enrolment_options(\stdClass $course, array $widgets, ?\core\url $returnurl = null): string {
        $email = trim((string) get_config('theme_ismos', 'enrolcontactemail'));
        if ($widgets || isguestuser() || $email === '' || !self::only_manual_enrolment((int) $course->id)) {
            return parent::enrolment_options($course, $widgets, $returnurl);
        }
        $name = format_string($course->fullname, true, ['context' => \context_course::instance($course->id), 'escape' => false]);
        $contact = $this->render_from_template('theme_ismos/enrol_contact', [
            'email' => $email,
            'mailto' => 'mailto:' . $email . '?subject=' . rawurlencode($name),
        ]);
        return parent::enrolment_options($course, [$contact], $returnurl);
    }

    /**
     * Whether manual enrolment is the course's only enabled enrolment method.
     *
     * @param int $courseid
     * @return bool
     */
    private static function only_manual_enrolment(int $courseid): bool {
        $enabled = enrol_get_plugins(true);
        $methods = [];
        foreach (enrol_get_instances($courseid, true) as $instance) {
            if (isset($enabled[$instance->enrol])) {
                $methods[$instance->enrol] = true;
            }
        }
        return array_keys($methods) === ['manual'];
    }

    /**
     * Front page main content: the landing page (hero with the next cohort, facts, paths, calendar, steps,
     * testimonials, final band) with the parts core renders that the landing does not replace. Everything is
     * inside the main landmark, after the skip link target, so the single h1 (the hero title) and its buttons
     * are not skipped. Other pages keep core's output.
     *
     * @return string
     */
    public function frontpage() {
        $parts = parent::frontpage();
        if ($this->page->pagetype !== 'site-index') {
            return $parts;
        }
        $data = (new \theme_ismos\output\frontpage())->export_for_template($this->output);
        $data['section1'] = $this->site_section1();
        $data['content'] = $parts;
        // Core may print empty wrappers when every part is replaced: only real content gets a column.
        $data['hasextra'] = trim(strip_tags($data['section1'] . $parts, '<img><form><input><iframe><video>')) !== '';
        return $this->output->render_from_template('theme_ismos/frontpage_main', $data);
    }

    /**
     * The course catalogue: all courses, or one level or subcategory, in the ISMOS look. Core's category select,
     * collapsed tree, "Expand all" and actions menu are not printed. Called before the page header, so the body
     * class can still be added.
     *
     * @param int|\stdClass|\core_course_category $category
     * @return string
     */
    public function course_category($category) {
        if (!in_array($this->page->pagetype, ['course-index', 'course-index-category'], true)) {
            return parent::course_category($category);
        }
        $this->page->add_body_class('ismos-catalog');
        $id = is_object($category) ? (int) $category->id : (int) $category;
        $data = (new \theme_ismos\local\catalogue())->export($id);
        $data['label'] = get_string('courses', 'theme_ismos');
        return $this->output->render_from_template('theme_ismos/catalogue', $data);
    }

    /**
     * Course search results in the catalogue's look (see catalogue::is_search_page). Searches by tag, block or activity
     * keep core's page.
     *
     * @param array $searchcriteria
     * @return string
     */
    public function search_courses($searchcriteria) {
        if (!\theme_ismos\local\catalogue::is_search_page($this->page)) {
            return parent::search_courses($searchcriteria);
        }
        $data = (new \theme_ismos\local\catalogue())->export_search(\theme_ismos\local\catalogue::search_words());
        $data['label'] = get_string('courses', 'theme_ismos');
        return $this->output->render_from_template('theme_ismos/catalogue', $data);
    }

    /**
     * Core echoes the site section 1 (summary and activities) before the front page main content, which
     * would put it above the hero and outside the content column. On the site index it is rendered by
     * frontpage() instead, so this returns nothing there.
     *
     * @return string
     */
    public function frontpage_section1() {
        if ($this->page->pagetype === 'site-index') {
            return '';
        }
        return parent::frontpage_section1();
    }

    /**
     * Site section 1 as core would render it on the front page, under the same conditions as
     * public/index.php (no custom front page include, and the site format has sections).
     *
     * @return string
     */
    protected function site_section1(): string {
        global $CFG, $SITE;
        require_once($CFG->dirroot . '/course/lib.php');
        if (!empty($CFG->customfrontpageinclude)) {
            return '';
        }
        $options = course_get_format($SITE)->get_format_options();
        if (empty($options['numsections'])) {
            return '';
        }
        return parent::frontpage_section1();
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
        // The landing page replaces the course list and the category tree: its paths and calendar show them.
        if ($this->page->pagetype === 'site-index' && in_array($contentsdivid, self::LANDING_PARTS, true)) {
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
