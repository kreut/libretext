<template>
  <div>
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-assignment-form'" />
    <AllFormErrors :all-form-errors="allFormErrors" :modal-id="'modal-form-errors-link-course-to-lms-form'" />
    <PageTitle v-if="canViewAssignments" :title="title" />
    <div class="vld-parent">
      <loading :active.sync="isLoading"
               :can-cancel="true"
               :is-full-page="true"
               :width="128"
               :height="128"
               color="#007BFF"
               background="#FFFFFF"
      />
      <b-modal id="modal-assignment-status"
               title="Explanation of Assignment Status"
               size="lg"
      >
        <table v-if="assignments.length" :key="`link-to-lms-${updateKey}`" class="table table-striped">
          <thead>
            <tr>
              <th scope="col">
                Status
              </th>
              <th scope="col">
                Explanation
              </th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <th>Upcoming</th>
              <td>
                Students cannot yet access the assignment.
              </td>
            </tr>

            <tr>
              <th>Open</th>
              <td>
                Students can access the assignment and submit.
              </td>
            </tr>
            <tr>
              <th>Closed</th>
              <td>
                Students can access the assignment yet no longer submit since the due date has passed.
              </td>
            </tr>
            <tr>
              <th>
                Released
              </th>
              <td>
                Students can access the assignment but they can't submit because the assignment is a delayed assignment
                and either the solutions are shown or the scores are released.
                Click the Main View toggle to show/hide the solutions or scores.
              </td>
            </tr>
          </tbody>
        </table>
      </b-modal>
      <b-modal
        id="modal-link-assignments-to-lms"
        title="Link Course to LMS"
      >
        <b-alert show variant="success">
          The course has been successfully linked. <span v-if="assignments.length">Assignments are now being linked.</span>
        </b-alert>
        <table v-if="assignments.length" :key="`link-to-lms-${updateKey}`" class="table table-striped">
          <thead>
            <tr>
              <th scope="col">
                Assignment
              </th>
              <th scope="col">
                Status
              </th>
            </tr>
          </thead>
          <tr v-for="(assignment,index) in assignments" :key="`assignments-${index}`">
            <td>{{ assignment.name }}</td>
            <td v-if="assignment.link_to_lms">
              <span :class="assignment.link_to_lms.message_class">{{ assignment.link_to_lms.message }}</span>
            </td>
          </tr>
        </table>
        <template #modal-footer="{ cancel, ok }">
          <b-button size="sm" @click="$bvModal.hide('modal-link-assignments-to-lms')">
            OK
          </b-button>
        </template>
      </b-modal>
      <b-modal
        id="modal-confirm-unlink-lms-course"
        title="Unlink course from LMS"
      >
        <p>
          Please confirm whether you would like to unlink this course from your LMS course <span
            class="font-weight-bold"
          >{{ course.lms_course }}</span>.
          If you unlink this course, your students will no longer be able to access the course through your LMS until
          you
          re-link the course.
        </p>
        <p>Though the course will become unlinked, it will not be deleted from your LMS nor from ADAPT.</p>
        <template #modal-footer="{ cancel, ok }">
          <b-button size="sm" @click="$bvModal.hide('modal-confirm-unlink-lms-course')">
            Cancel
          </b-button>
          <b-button size="sm"
                    variant="primary"
                    @click="unlinkCourseFromLMS"
          >
            Unlink Course
          </b-button>
        </template>
      </b-modal>
      <b-modal
        id="modal-confirm-add-untethered-assignment"
        ref="modal"
        title="Confirm New Untethered Assignment"
      >
        <p>
          You are about to {{ addAssignmentIsImport ? 'import' : 'add' }} an untethered assignment to a tethered course.
          If you are presenting the course within
          the context
          of a Libretext book, you will have to manually create a page with this assignment so that your students can
          access it.
        </p>
        <p>
          If, on the other hand, you are presenting your assignments within the ADAPT platform, the only consequence of
          adding an
          untethered assignment is that these will not be auto-updated via an Alpha course.
        </p>
        <template #modal-footer="{ cancel, ok }">
          <b-button size="sm" @click="$bvModal.hide('modal-confirm-add-untethered-assignment')">
            Cancel
          </b-button>
          <b-button size="sm" variant="primary"
                    @click="$bvModal.hide('modal-confirm-add-untethered-assignment');addUntetheredAssignment()"
          >
            {{ addAssignmentIsImport ? 'Import' : 'Add' }} Untethered Assignment
          </b-button>
        </template>
      </b-modal>
      <b-modal
        id="modal-cannot-delete-beta-assignment"
        ref="modal"
        title="Cannot Delete"
        size="sm"
        hide-footer
      >
        This assignment is a Beta assignment. Since it is tethered to a corresponding assigment in an
        Alpha course, it cannot be deleted.
      </b-modal>
      <b-modal
        id="modal-assignment-properties"
        ref="modal"
        title="Assignment Properties"
        ok-title="Submit"
        size="lg"
        no-close-on-backdrop
        @hidden="resetAssignmentForm(form,assignmentId)"
        @shown="updateModalToggleIndex('modal-assignment-properties')"
      >
        <AssignmentProperties
          :key="assignmentId"
          :assignment-groups="assignmentGroups"
          :form="form"
          :course-id="parseInt(courseId)"
          :course-start-date="course.start_date"
          :course-end-date="course.end_date"
          :assignment-id="assignmentId"
          :is-beta-assignment="isBetaAssignment"
          :lms="!!lms"
          :lms-api="form.lms_api"
          :has-submissions-or-file-submissions="hasSubmissionsOrFileSubmissions"
          :is-alpha-course="Boolean(course.alpha)"
          :is-formative-course="Boolean(course.formative)"
          :is-formative-assignment="Boolean(isFormativeAssignment) || form.isFormativeAssignment"
          :overall-status-is-not-open="overallStatusIsNotOpen"
          :owns-all-questions="Boolean(ownsAllQuestions)"
          :anonymous-users="Boolean(course.anonymous_users)"
          @populateFormWithAssignmentTemplate="populateFormWithAssignmentTemplate"
        />
        <template #modal-footer="{ cancel, ok }">
          <b-button size="sm" @click="$bvModal.hide('modal-assignment-properties')">
            Cancel
          </b-button>
          <b-button v-show="!savingAssignment"
                    size="sm"
                    variant="primary"
                    @click="handleSubmitAssignmentInfo()"
          >
            Save
          </b-button>
          <span v-show="savingAssignment" class="pl-2">
            <b-spinner small type="grow" />
            Saving...
          </span>
        </template>
      </b-modal>
      <b-modal
        id="modal-assign-tos-to-view"
        ref="modal"
        title="Assigned To"
        size="lg"
      >
        <AssignTosToView ref="assignTosModal" :assign-tos-to-view="assignTosToView" />
      </b-modal>

      <b-modal
        id="modal-create-assignment-from-template"
        ref="modal"
        title="Create Assignment From Template"
      >
        <b-form-group
          id="create_assignment_from_template_level"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Level"
          label-for="Level"
        >
          <b-form-radio-group
            v-model="createAssignmentFromTemplateForm.level"
            stacked
          >
            <b-form-radio value="properties_and_questions">
              Properties and questions
            </b-form-radio>
            <b-form-radio value="properties_and_not_questions">
              Just the properties
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>

        <b-form-group
          id="create_assignment_from_template_assign_to_groups"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Assign To's"
          label-for="Assign To's"
        >
          <b-form-radio-group
            v-model="createAssignmentFromTemplateForm.assign_to_groups"
            stacked
          >
            <b-form-radio value="1">
              Copy groups and associated times
            </b-form-radio>
            <b-form-radio value="0">
              Don't copy groups and associated times
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
        <template #modal-footer>
          <b-button
            size="sm"
            class="float-right"
            @click="$bvModal.hide('modal-create-assignment-from-template')"
          >
            Cancel
          </b-button>
          <b-button
            variant="primary"
            size="sm"
            class="float-right"
            @click="handleCreateAssignmentFromTemplate"
          >
            Yes, copy assignment!
          </b-button>
        </template>
      </b-modal>
      <b-modal
        id="modal-import-assignment"
        ref="modal"
        title="Import Assignment"
      >
        <b-container>
          <b-row class="pb-4">
            <b-form-select id="collections"
                           v-model="collection"
                           :options="collectionOptions"
                           @change="importableCourse= null;importableAssignment=null;getCoursesByCollection($event)"
            />
          </b-row>
          <b-row class="pb-2">
            <b-form-select
              id="courses"
              v-model="importableCourse"
              :disabled="collection === null"
              :options="importableCourseOptions"
              @change="getImportableAssignments($event)"
            />
          </b-row>
          <b-row class="pb-4">
            <b-form-select
              id="assignments"
              v-model="importableAssignment"
              :disabled="importableCourse === null"
              :options="importableAssignmentOptions"
            />
          </b-row>
        </b-container>
        <b-form-group
          v-if="importableAssignment !== null"
          id="import_level"
          label-cols-sm="4"
          label-cols-lg="3"
          label="Import Level"
          label-for="Import Level"
        >
          <b-form-radio-group v-model="importAssignmentForm.level" stacked>
            <b-form-radio value="properties_and_questions">
              Properties and questions
            </b-form-radio>
            <b-form-radio value="properties_and_not_questions">
              Just the properties
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
        <b-form-group
          v-show="course.lms && importableAssignment !== null"
          label-cols-sm="4"
          label-cols-lg="3"
          label-for="lms_grade_passback"
        >
          <template v-slot:label>
            LMS Grade Passback*
            <QuestionCircleTooltip :id="'lms_grade_passback_tooltip'" />
            <b-tooltip target="lms_grade_passback_tooltip"
                       delay="250"
                       triggers="hover focus"
            >
              With the automatic option, grades are passed back to your LMS each time that your one of your students
              submits their response to a question.
              For delayed grading, the manual option is recommended.
            </b-tooltip>
          </template>
          <b-form-radio-group
            v-model="importAssignmentForm.lms_grade_passback"
            required
            stacked
          >
            <b-form-radio name="lms_grade_passback" value="automatic">
              Automatic
            </b-form-radio>
            <b-form-radio name="lms_grade_passback" value="manual">
              Manual
            </b-form-radio>
          </b-form-radio-group>
        </b-form-group>
        <template #modal-footer>
          <b-button
            size="sm"
            class="float-right"
            @click="$bvModal.hide('modal-import-assignment')"
          >
            Cancel
          </b-button>
          <b-button
            variant="primary"
            size="sm"
            class="float-right"
            @click="handleImportAssignment"
          >
            Yes, import assignment!
          </b-button>
        </template>
      </b-modal>

      <b-modal
        id="modal-delete-assignment"
        ref="modal"
        :title="tetheredBetaAssignmentExists ? 'Cannot Delete Assignment' :'Confirm Delete Assignment'"
        :hide-footer="tetheredBetaAssignmentExists"
      >
        <div v-show="!tetheredBetaAssignmentExists">
          <p>
            By deleting the assignment, you will also delete all student scores associated with the assignment.
          </p>
          <b-alert :show="hasLmsAssignmentId(assignmentId)" variant="danger">
            Please note that the assignment and all associated scores on your LMS will be deleted as
            well.
          </b-alert>
          <p><strong>Once an assignment is deleted, it can not be retrieved!</strong></p>
        </div>
        <div v-show="tetheredBetaAssignmentExists">
          <p>
            Since this is an Alpha course and this assignment is already tethered to a Beta assignment, you cannot
            delete this assignment. However, you
            can always hide this
            assignment from your own students.
          </p>
        </div>
        <template #modal-footer>
          <b-button
            size="sm"
            class="float-right"
            @click="$bvModal.hide('modal-delete-assignment')"
          >
            Cancel
          </b-button>
          <b-button
            variant="primary"
            size="sm"
            class="float-right"
            @click="handleDeleteAssignment"
          >
            Yes, delete assignment!
          </b-button>
        </template>
      </b-modal>

      <b-container v-if="canViewAssignments">
        <div v-show="betaCoursesInfo.length>0">
          <b-alert variant="info" :show="true">
            <span class="font-weight-bold">
              This is an Alpha course with tethered Beta courses.  Any new assignments that are created in
              this course will be created in the associated Beta courses.
            </span>
          </b-alert>
        </div>
        <div v-show="lms">
          <b-alert variant="info" :show="true">
            <div v-if="!course.lms_course_id">
              This is a course which is being served through your LMS. You will create your assignments
              in ADAPT including determining due dates, but will use your LMS's gradebook.
            </div>
            <div v-if="course.lms_has_api_key && enableCanvasAPI">
              <div v-if="course.lms_course_id">
                <div>
                  This course is directly linked to the LMS course <span class="font-weight-bold">{{ course.lms_course_name }}</span>.
                  <b-button size="sm" variant="info" @click="$bvModal.show('modal-confirm-unlink-lms-course')">
                    Unlink Course
                  </b-button>
                </div>
                <div>
                  <a href="#" @click.prevent="$bvModal.show('modal-lms-linking-process')">Learn more about the
                    linking process.</a>
                </div>
                <b-modal id="modal-lms-linking-process"
                         title="Linking to your LMS"
                         hide-footer
                         size="lg"
                >
                  <p>The following table describes how ADAPT and your LMS are linked.</p>
                  <table class="table table-striped">
                    <thead>
                      <tr>
                        <th>ADAPT</th>
                        <th>LMS</th>
                      </tr>
                    </thead>
                    <tr>
                      <td>
                        Course start/end dates
                      </td>
                      <td>The period for which your assignments are unlocked</td>
                    </tr>
                    <tr>
                      <td>
                        Assignment Name
                      </td>
                      <td>Assignment Name</td>
                    </tr>
                    <tr>
                      <td>
                        Assign Tos
                      </td>
                      <td>Determines when students can access assignment questions</td>
                    </tr>
                    <tr>
                      <td>
                        Instructions
                      </td>
                      <td>Assignment Description</td>
                    </tr>
                    <tr>
                      <td>
                        Assignment Groups
                      </td>
                      <td>Assignment Groups</td>
                    </tr>
                    <tr>
                      <td>
                        Show/Hide
                      </td>
                      <td>Publish/Unpublish individual assignments</td>
                    </tr>
                    <tr>
                      <td>
                        Add/remove questions
                      </td>
                      <td>Total assignment points updated</td>
                    </tr>
                  </table>
                </b-modal>
              </div>
              <div v-else>
                <div v-if="!course.lms_has_access_token">
                  <p class="mt-3">
                    To link your course to one of LMS courses, please
                    <GrantLmsApiAccess :key="`grant-lms-api-access-${course.id}`"
                                       :course-id="course.id"
                    />
                  </p>
                </div>
                <div v-if="course.lms_has_access_token">
                  <p class="mt-3">
                    Please begin by choosing a course from your LMS to link to. Then, all assignments created within
                    ADAPT
                    will automatically
                    be created in your LMS.
                  </p>
                  <b-form-group
                    label-cols-sm="3"
                    label-cols-lg="2"
                    label-size="sm"
                    label="Link to LMS course"
                  >
                    <b-form-select v-model="linkCourseToLMSForm.lms_course_id"
                                   :options="lmsCourseOptions"
                                   size="sm"
                                   :class="{ 'is-invalid': linkCourseToLMSForm.errors.has('lms_course_id') }"
                                   style="width: 200px"
                                   @change="linkCourseToLMS"
                    />

                    <span v-if="processingLinkCourseToLMS" class="pl-2">
                      <b-spinner small type="grow" />
                      Processing...
                    </span>
                    <has-error :form="linkCourseToLMSForm" field="lms_course_id" />
                  </b-form-group>
                </div>
              </div>
            </div>
          </b-alert>
        </div>
        <b-row class="mb-4" align-h="end">
          <b-col v-if="[2,4].includes(user.role) && !course.formative" lg="3">
            <b-form-select
              v-if="assignmentGroupOptions.length>1"
              v-model="chosenAssignmentGroup"
              title="Filter by assignment group"
              :options="assignmentGroupOptions"
              @change="updateAssignmentGroupFilter(courseId)"
            />
          </b-col>
          <b-col lg="9">
            <span class="float-right">
              <b-button v-if="user && [2,5].includes(user.role)"
                        class="ml-5 mr-1"
                        size="sm"
                        variant="primary"
                        @click="addAssignmentIsImport=false;confirmInitAddAssignment()"
              >
                New Assignment
              </b-button>
              <span v-if="!course.formative">
                <b-button v-if="(user && user.role === 2)"
                          class="mr-1"
                          size="sm"
                          variant="outline-primary"
                          @click="addAssignmentIsImport=true;confirmInitImportAssignment()"
                >
                  Import Assignment
                </b-button>
                <b-button
                  v-if="[2,4].includes(user.role)"
                  :class="(user && user.role === 4) ? 'float-right' : ''"
                  size="sm"
                  @click="getGradeBook()"
                >
                  Course Gradebook
                </b-button>
              </span>
              <b-button
                v-if="user && user.role === 2"
                :class="(user && user.role === 4) ? 'float-right' : ''"
                size="sm"
                variant="info"
                @click="$router.push(`/instructors/courses/${courseId}/properties`)"
              >
                Course Properties
              </b-button>
            </span>
          </b-col>
        </b-row>
      </b-container>
      <div v-show="hasAssignments" class="table-responsive">
        <toggle-button
          v-if="[2,4].includes(user.role) && !course.formative"
          tabindex="0"
          :width="125"
          :value="view === 'main view'"
          :sync="true"
          :font-size="14"
          :margin="4"
          :color="toggleColors"
          :labels="{checked: 'Main View', unchecked: 'Control Panel'}"
          @change="updateView"
        />
        <p v-show="atLeastOneAssignmentNotIncludedInWeightedAverage">
          Submissions for assignments marked with an asterisk (<span class="text-danger">*</span>) will not be included
          in when computing the final weighted average.
        </p>
        <table class="table table-striped" aria-label="Assignment List">
          <thead>
            <tr>
              <th scope="col">
                Assignment Name
              </th>
              <th v-if="view === 'control panel'" scope="col">
                Scores
              </th>
              <th v-if="view === 'control panel'" scope="col">
                Solutions
              </th>
              <th v-if="view === 'control panel'" scope="col">
                Statistics
              </th>
              <th v-if="view === 'control panel'" scope="col">
                Points Per Question
              </th>
              <th v-if="view === 'control panel' && user.role ===2" scope="col" style="width:170px">
                Student Names
                <QuestionCircleTooltip :id="'viewable-by-graders-tooltip'" />
                <b-tooltip target="viewable-by-graders-tooltip"
                           delay="500"
                           triggers="hover focus"
                >
                  You can optionally hide your students' names from your graders to avoid any sort of
                  conscious or subconscious bias.
                </b-tooltip>
              </th>
              <th v-if="view === 'control panel' && user.role ===2" scope="col">
                Question URL View
                <QuestionCircleTooltip id="question-url-view-tooltip" />
                <b-tooltip target="question-url-view-tooltip"
                           delay="500"
                           triggers="hover focus"
                >
                  You can provide your students with a URL taking them directly to any question in the assignment, found
                  within a given question's properties. From this question you can either show the entire assignment
                  or just limit the view to that specific question.
                </b-tooltip>
              </th>
              <th v-if="view === 'main view' && [2,4].includes(user.role)" scope="col">
                Shown
              </th>
              <th v-if="view === 'main view' && [2,4].includes(user.role)" scope="col">
                Group
              </th>
              <th v-if="view === 'main view' && [2,4].includes(user.role)" scope="col">
                Available On
              </th>
              <th v-if="view === 'main view' && [2,4].includes(user.role)" scope="col">
                Due
              </th>
              <th v-show="view === 'main view' && [2,4].includes(user.role)" scope="col">
                Status
                <span @mouseover="showAssignmentStatusModal()">
                  <QuestionCircleTooltip />
                </span>
              </th>
              <th v-if="view === 'main view'" scope="col" :style="lms ? 'width: 145px' :'width: 115px'">
                Actions
              </th>
            </tr>
          </thead>
          <tbody is="draggable" :key="assignments.length"
                 v-model="assignments"
                 tag="tbody"
                 :options="{handle: '.handle'}"
                 @end="saveNewOrder"
          >
            <tr
              v-for="assignment in assignments"
              v-show="chosenAssignmentGroup === null || assignment.assignment_group === chosenAssignmentGroupText"
              :key="assignment.id"
              :style="!assignment.shown && user.role === 2 ? 'background: #ffe8e7' : ''"
            >
              <th scope="row" style="width:300px">
                <b-icon icon="list" class="handle" />
                <a v-show="assignment.is_beta_assignment"
                   :id="getTooltipTarget('betaAssignment',assignment.id)"
                   href="#"
                   class="text-muted"
                >
                  &beta;
                </a>
                <b-tooltip :target="getTooltipTarget('betaAssignment',assignment.id)"
                           delay="500"
                           triggers="hover focus"
                >
                  This Beta assignment was automatically generated from its corresponding Alpha course. Because of the
                  tethered
                  nature, you cannot remove the assignment nor add/remove assessments.
                </b-tooltip>
                <a v-show="Boolean(course.alpha)"
                   :id="getTooltipTarget('alphaCourse',assignment.id)"
                   href="#"
                   class="text-muted"
                >&alpha; </a>
                <b-tooltip :target="getTooltipTarget('alphaCourse',assignment.id)"
                           delay="500"
                           triggers="hover focus"
                >
                  This assignment is part of an Alpha course. Any assignments/assessments that you create or remove will
                  be reflected in the tethered Beta courses.
                </b-tooltip>
                <span v-show="assignment.source === 'a'">
                  <span v-show="isLocked(assignment.has_submissions_or_file_submissions) && !isFormative (assignment)"
                        :id="getTooltipTarget('getQuestions',assignment.id)"
                  >
                    <b-icon
                      icon="lock-fill"
                    />
                  </span>
                </span>
                <router-link v-if="assignment.source !== 'x'"
                             :to="{ name: 'instructors.assignments.questions',params:{assignmentId:assignment.id}}"
                >
                  {{ assignment.name }}
                </router-link>
                <a v-if="assignment.source === 'x'" href="" @click.prevent="showExternalAssignmentNoty()">{{ assignment.name }}</a> <span v-show="!assignment.include_in_weighted_average"
                                                                                                                                          :id="`not-shown-assignment-tooltip-${assignment.id}`" class="text-danger"
                >*</span>
                <b-tooltip :target="`not-shown-assignment-tooltip-${assignment.id}`"
                           delay="250"
                           triggers="hover focus"
                >
                  {{ assignment.name }} will not be included when computing the final weighted average.
                </b-tooltip>
                <span v-if="user && [2,4].includes(user.role)">
                  <b-tooltip :target="getTooltipTarget('getQuestions',assignment.id)"
                             delay="500"
                             triggers="hover focus"
                  >
                    <div v-html="getLockedQuestionsMessage(assignment)" />
                  </b-tooltip>

                </span>
              </th>
              <td v-if="view === 'control panel'">
                <div v-if="isFormative (assignment)">
                  N/A
                </div>
                <div v-if="!isFormative (assignment)">
                  <ShowScoresToggle :key="`show-scores-toggle-${assignment.id}`"
                                    :assignment="assignment"
                  />
                </div>
              </td>
              <td v-if="view === 'control panel'">
                <div v-if="isFormative (assignment)">
                  N/A
                </div>
                <div v-if="!isFormative (assignment)">
                  <ShowSolutionsToggle :key="`show-solutions-toggle-${assignment.id}`"
                                       :assignment="assignment"
                  />
                </div>
              </td>
              <td v-if="view === 'control panel'">
                <div v-if="isFormative (assignment)">
                  N/A
                </div>
                <div v-if="!isFormative (assignment)">
                  <StudentsCanViewAssignmentStatisticsToggle
                    :key="`students-can-view-assignment-statistics-toggle-${assignment.id}`"
                    :assignment="assignment"
                  />
                </div>
              </td>
              <td v-if="view === 'control panel'">
                <div v-if="isFormative (assignment)">
                  N/A
                </div>
                <div v-if="!isFormative (assignment)">
                  <ShowPointsPerQuestionToggle
                    :key="`students-can-view-assignment-statistics-toggle-${assignment.id}`"
                    :assignment="assignment"
                  />
                </div>
              </td>
              <td v-if="view === 'control panel' && user.role === 2">
                <div v-if="isFormative (assignment)">
                  N/A
                </div>
                <div v-if="!isFormative (assignment)">
                  <GradersCanSeeStudentNamesToggle
                    :key="`students-can-view-assignment-statistics-toggle-${assignment.id}`"
                    :assignment="assignment"
                  />
                </div>
              </td>
              <td v-if="view === 'control panel' && user.role === 2">
                <QuestionUrlViewToggle :key="`question-url-view-toggle-${assignment.id}`" :assignment="assignment" />
              </td>
              <td v-if="view === 'main view' && [2,4].includes(user.role)">
                <div v-if="isFormative (assignment)">
                  N/A
                </div>
                <div v-if="!isFormative (assignment)">
                  <toggle-button
                    tabindex="0"
                    :width="57"
                    :value="Boolean(assignment.shown)"
                    :sync="true"
                    :font-size="14"
                    :margin="4"
                    :color="toggleColors"
                    :aria-label="Boolean(assignment.shown) ? `${assignment.name} shown` : `${assignment.name} not shown`"
                    :labels="{checked: 'Yes', unchecked: 'No'}"
                    @change="submitShowAssignment(assignment)"
                  />
                </div>
              </td>
              <td v-if="view === 'main view' && [2,4].includes(user.role)">
                <div v-if="isFormative (assignment)">
                  N/A
                </div>
                <div v-if="!isFormative (assignment)">
                  {{ assignment.assignment_group }}
                </div>
              </td>
              <td v-if="view === 'main view' && [2,4].includes(user.role)">
                <div v-if="isFormative (assignment)">
                  N/A
                </div>
                <div v-if="!isFormative (assignment)">
                  <span v-if="assignment.assign_tos.length === 1">
                    {{ $moment(assignment.assign_tos[0].available_from, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') }}
                    {{ $moment(assignment.assign_tos[0].available_from, 'YYYY-MM-DD HH:mm:ss A').format('h:mm A') }}
                  </span>
                  <span v-if="assignment.assign_tos.length > 1">
                    <b-button variant="primary" size="sm" @click="viewAssignTos(assignment.assign_tos)">View</b-button>
                  </span>
                </div>
              </td>
              <td v-if="view === 'main view' && [2,4].includes(user.role)" style="width:200px">
                <div v-if="isFormative (assignment)">
                  N/A
                </div>
                <div v-if="!isFormative (assignment)">
                  <span v-if="assignment.assign_tos.length === 1">
                    <span v-if="!showFinalSubmissionDeadline(assignment.assign_tos[0])">
                      {{ $moment(assignment.assign_tos[0].due, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY') }}
                      {{ $moment(assignment.assign_tos[0].due, 'YYYY-MM-DD HH:mm:ss A').format('h:mm A') }}
                    </span>
                    <span v-if="showFinalSubmissionDeadline(assignment.assign_tos[0])">
                      {{
                        $moment(assignment.assign_tos[0].final_submission_deadline, 'YYYY-MM-DD HH:mm:ss A').format('M/D/YY')
                      }}
                      {{
                        $moment(assignment.assign_tos[0].final_submission_deadline, 'YYYY-MM-DD HH:mm:ss A').format('h:mm A')
                      }}
                    </span>
                    <span v-show="assignment.assign_tos[0].status === 'Late'">*</span>
                  </span>
                </div>
              </td>
              <td v-if="view === 'main view' && [2,4].includes(user.role)">
                <div v-if="isFormative (assignment)">
                  N/A
                </div>
                <div v-if="!isFormative (assignment)">
                  <span v-if="assignment.assign_tos.length === 1"
                        :class="getStatusTextClass(assignment.assign_tos[0].status)"
                  >{{ assignment.assign_tos[0].status }}</span>
                  <span v-if="assignment.assign_tos.length > 1" v-html="assignment.overall_status" />
                </div>
              </td>
              <td v-if="view === 'main view'">
                <div class="mb-0">
                  <b-tooltip :target="getTooltipTarget('viewSubmissionFiles',assignment.id)"
                             delay="500"
                             triggers="hover focus"
                  >
                    <span v-show="assignment.num_to_grade === 0">Grading</span><span v-show="assignment.num_to_grade > 0">There are {{
                      assignment.num_to_grade
                    }}
                      submission<span v-show="assignment.num_to_grade >1">s</span> which still need to be graded.</span>
                  </b-tooltip>
                  <a v-if="user && user.role !== 5 && !isFormative (assignment)"
                     v-show="assignment.source === 'a' & assignment.submission_files !== '0'"
                     :id="getTooltipTarget('viewSubmissionFiles',assignment.id)"
                     href=""
                     class="pr-1"
                     @click.prevent="getSubmissionFileView(assignment.id, assignment.submission_files)"
                  >

                    <b-icon-check-circle
                      :class="assignment.num_to_grade > 0 ? 'text-warning' : 'text-muted'"
                      :aria-label="`Open Grader for ${assignment.name}`"
                    />
                  </a>
                  <LMSGradePassback v-show="assignment.lms_grade_passback === 'manual'"
                                    :key="`LMSGradePassback-${assignment.id}`" :assignment="assignment"
                                    class="pr-2"
                  />
                  <span v-show="user && [2,5].includes(user.role)">
                    <b-tooltip :target="getTooltipTarget('editAssignmentProperties',assignment.id)"
                               delay="500"
                               triggers="hover focus"
                    >
                      Assignment Properties
                    </b-tooltip>
                    <a :id="getTooltipTarget('editAssignmentProperties',assignment.id)"
                       href=""
                       class="pr-1"
                       @click.prevent="initEditAssignmentProperties(assignment)"
                    >
                      <b-icon
                        icon="gear"
                        class="text-muted"
                        :aria-label="`Assignment properties for ${assignment.name}`"
                      />
                    </a>
                    <b-tooltip :target="getTooltipTarget('createAssignmentFromTemplate',assignment.id)"
                               triggers="hover focus"
                               delay="500"
                    >
                      Create Assignment From Template
                    </b-tooltip>
                    <a :id="getTooltipTarget('createAssignmentFromTemplate',assignment.id)"
                       href="#"
                       class="pr-1"
                       :aria-label="`Create assignment from ${assignment.name} template`"
                       @click="initCreateAssignmentFromTemplate(assignment.id)"
                    >
                      <font-awesome-icon
                        :icon="copyIcon"
                        class="text-muted"
                      />
                    </a>
                    <b-tooltip :target="getTooltipTarget('deleteAssignment',assignment.id)"
                               delay="500"
                               triggers="hover focus"
                    >
                      Delete Assignment
                    </b-tooltip>
                    <a :id="getTooltipTarget('deleteAssignment',assignment.id)"
                       href=""
                       @click.prevent="deleteAssignment(assignment)"
                    >
                      <b-icon icon="trash" class="text-muted" :aria-label="`Delete ${assignment.name}`" />
                    </a>
                  </span>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="!hasAssignments">
        <div class="mt-4">
          <b-alert :show="showNoAssignmentsAlert" variant="warning">
            <a href="#" class="alert-link">This course currently
              has
              no assignments.</a>
          </b-alert>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'
import Form from 'vform'
import { mapGetters } from 'vuex'
import { ToggleButton } from 'vue-js-toggle-button'
import { getTooltipTarget, initTooltips } from '~/helpers/Tooptips'
import { getStatusTextClass } from '~/helpers/AssignTosStatus'
import {
  isLocked,
  getAssignments,
  isLockedMessage,
  initAssignmentGroupOptions,
  updateAssignmentGroupFilter
  , checkIfReleased
} from '~/helpers/Assignments'

import {
  initAddAssignment,
  editAssignmentProperties,
  getAssignmentGroups,
  prepareForm,
  assignmentForm,
  resetAssignmentForm
} from '~/helpers/AssignmentProperties'
import { updateModalToggleIndex } from '~/helpers/accessibility/fixCKEditor'
import AssignmentProperties from '~/components/AssignmentProperties'
import AssignTosToView from '~/components/AssignTosToView'

import Loading from 'vue-loading-overlay'
import 'vue-loading-overlay/dist/vue-loading.css'
import draggable from 'vuedraggable'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { faCopy } from '@fortawesome/free-regular-svg-icons'
import AllFormErrors from '~/components/AllFormErrors'
import ShowScoresToggle from '~/components/ShowScoresToggle'
import ShowSolutionsToggle from '~/components/ShowSolutionsToggle'
import StudentsCanViewAssignmentStatisticsToggle from '~/components/StudentsCanViewAssignmentStatisticsToggle'
import ShowPointsPerQuestionToggle from '~/components/ShowPointsPerQuestionToggle'
import GradersCanSeeStudentNamesToggle from '~/components/GradersCanSeeStudentNamesToggle'
import { fixInvalid } from '~/helpers/accessibility/FixInvalid'
import QuestionUrlViewToggle from '~/components/QuestionUrlViewToggle.vue'
import LMSGradePassback from '~/components/LMSGradePassback.vue'
import GrantLmsApiAccess from '~/components/GrantLmsApiAccess.vue'
import { isMobile } from '~/helpers/mobileCheck'

export default {
  middleware: 'auth',
  components: {
    GrantLmsApiAccess,
    LMSGradePassback,
    QuestionUrlViewToggle,
    ToggleButton,
    Loading,
    AssignmentProperties,
    AssignTosToView,
    draggable,
    FontAwesomeIcon,
    AllFormErrors,
    ShowScoresToggle,
    ShowSolutionsToggle,
    StudentsCanViewAssignmentStatisticsToggle,
    ShowPointsPerQuestionToggle,
    GradersCanSeeStudentNamesToggle
  },
  data: () => ({
    enableCanvasAPI: false,
    savingAssignment: false,
    updateKey: 0,
    processingLinkAssignmentsToLMS: false,
    processingLinkCourseToLMS: false,
    linkCourseToLMSForm: new Form({
      lms_course_id: 0
    }),
    lmsCourseOptions: [],
    ownsAllQuestions: false,
    isFormativeAssignment: false,
    tetheredBetaAssignmentExists: false,
    atLeastOneAssignmentNotIncludedInWeightedAverage: false,
    importableAssignment: null,
    importableAssignmentOptions: [{ value: null, text: 'Please choose an assignment' }],
    importableCourse: null,
    importableCourseOptions: [{ value: null, text: 'Please choose a course' }],
    collection: null,
    collectionOptions: [{ value: null, text: 'Please choose a collection' },
      { value: 'my_courses', text: 'My Courses' },
      { value: 'commons', text: 'Commons' },
      { value: 'all_public_courses', text: 'All Public Courses' }
    ],
    view: 'main view',
    hasSubmissionsOrFileSubmissions: false,
    toggleColors: window.config.toggleColors,
    lms: false,
    isBetaAssignment: false,
    overallStatusIsNotOpen: false,
    copyIcon: faCopy,
    addAssignmentIsImport: false,
    isBetaCourse: false,
    betaCoursesInfo: [],
    allFormErrors: [],
    assignmentGroups: [],
    form: assignmentForm,
    assessmentType: '',
    chosenAssignmentGroupText: null,
    chosenAssignmentGroup: null,
    assignmentGroupOptions: [],
    createAssignmentFromTemplateForm: new Form({
      level: 'properties_and_questions',
      assign_to_groups: 1
    }),
    createAssignmentFromTemplateAssignmentId: 0,
    course: '',
    assignTosToView: [],
    currentOrderedAssignments: [],
    importAssignmentForm: new Form({
      level: 'properties_and_questions',
      lms_grade_passback: 'automatic'
    }),
    assignmentGroupForm: new Form({
      assignment_group: ''
    }),
    allAssignments: [],
    title: '',
    isLoading: false,
    solutionsReleased: 0,
    assignmentId: 0, // if there's an assignmentId it's an update
    assignments: [],
    showPointsPerQuestionTooltip: {
      fallbackPlacement: ['right'],
      placement: 'right',
      title: 'In case you only grade a random subset of questions, you can hide the number of points per question so that your students won\'t know which questions you\'ll be grading.'
    },
    completedOrCorrectOptions: [
      { item: 'correct', name: 'correct' },
      { item: 'completed', name: 'completed' }
    ],
    courseId: false,
    hasAssignments: false,
    has_submissions_or_file_submissions: false,
    canViewAssignments: false,
    showNoAssignmentsAlert: false
  }),
  computed: mapGetters({
    user: 'auth/user'
  }),
  created () {
    this.courseId = this.$route.params.courseId
    this.getAssignments = getAssignments
    this.isLocked = isLocked
    this.isLockedMessage = isLockedMessage
    this.initAssignmentGroupOptions = initAssignmentGroupOptions
    this.updateAssignmentGroupFilter = updateAssignmentGroupFilter
    this.resetAssignmentForm = resetAssignmentForm
    this.updateModalToggleIndex = updateModalToggleIndex
  },
  beforeDestroy () {
    window.removeEventListener('keydown', this.quickSave)
  },
  async mounted () {
    window.addEventListener('keydown', this.quickSave)
    this.initAddAssignment = initAddAssignment
    this.editAssignmentProperties = editAssignmentProperties
    this.prepareForm = prepareForm
    this.getTooltipTarget = getTooltipTarget
    initTooltips(this)
    this.isLoading = true
    if (!await this.getCourseInfo()) {
      this.isLoading = false
      return false
    }
    this.assignmentGroups = await getAssignmentGroups(this.courseId, this.$noty)
    if (this.user.role === 2) {
      await this.getAssignmentGroupFilter(this.courseId)
    }
    if (this.user) {
      if (![2, 4, 5].includes(this.user.role)) {
        this.isLoading = false
        this.$noty.error('You are not allowed to access this page.')
        return false
      }
      await this.getAssignments()
      this.currentOrderedAssignments = this.assignments
      for (let i = 0; i < this.assignments.length; i++) {
        if (!this.assignments[i].include_in_weighted_average) {
          this.atLeastOneAssignmentNotIncludedInWeightedAverage = true
        }
      }
      this.initAssignmentGroupOptions(this.assignments)
      if (this.user.role === 2) {
        this.updateAssignmentGroupFilter(this.courseId)
      }
    }
  },
  methods: {
    isMobile,
    checkIfReleased,
    getStatusTextClass,
    showAssignmentStatusModal () {
      this.$bvModal.show('modal-assignment-status')
    },
    async linkCourseToLMS () {
      if (!this.linkCourseToLMSForm.lms_course_id) {
        return
      }
      this.processingLinkCourseToLMS = true
      try {
        const { data } = await this.linkCourseToLMSForm.patch(`/api/courses/${this.courseId}/link-to-lms`)
        if (data.type === 'success') {
          await this.getCourseInfo()
          await this.linkAssignmentsToLMS()
        } else {
          this.$noty[data.type](data.message)
          this.linkCourseToLMSForm.lms_course_id = 0
        }
      } catch (error) {
        this.$noty.error(error.message)
        this.linkCourseToLMSForm.lms_course_id = 0
      }
      this.processingLinkCourseToLMS = false
    },

    async linkAssignmentsToLMS () {
      for (let i = 0; i < this.assignments.length; i++) {
        this.assignments[i].link_to_lms = { message: 'Pending', type: '', message_class: 'text-warning' }
      }
      this.$bvModal.show('modal-link-assignments-to-lms')
      if (this.assignments.length) {
        this.processingLinkAssignmentsToLMS = true
      }
      for (let i = 0; i < this.assignments.length; i++) {
        let assignment = this.assignments[i]
        try {
          const { data } = await axios.patch(`/api/assignments/${assignment.id}/link-to-lms`)
          this.assignments[i].link_to_lms =
            {
              message: data.message,
              type: data.type,
              message_class: data.type === 'success' ? 'text-success' : 'text-danger'
            }
        } catch (error) {
          this.assignments[i].link_to_lms =
            {
              message: error.message,
              type: 'error'
            }
        }
        this.updateKey++
      }
      this.processingLinkAssignmentsToLMS = false

      /* START:  Linking works but why doesn't it show up in the table?
        How to get the initial key?
        Be able to link/unlink manually each assignment
What assignment parameters??? */
    },
    async unlinkCourseFromLMS () {
      try {
        const { data } = await axios.patch(`/api/courses/${this.courseId}/unlink-from-lms`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.$bvModal.hide('modal-confirm-unlink-lms-course')
          await this.getCourseInfo()
          this.linkCourseToLMSForm.lms_course_id = 0
          this.$forceUpdate()
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    hasLmsAssignmentId (assignmentId) {
      const assignment = this.assignments.find(assignment => assignment.id === assignmentId)
      return assignment && assignment.lms_assignment_id
    },
    showFinalSubmissionDeadline (assignTo) {
      return assignTo.final_submission_deadline && this.$moment().isAfter(this.$moment(assignTo.due))
    },
    isFormative (assignment) {
      return assignment.formative || this.course.formative
    },
    quickSave (event) {
      if (event.ctrlKey && event.key === 'S') {
        this.handleSubmitAssignmentInfo()
      }
    },
    initEditAssignmentProperties (assignment) {
      this.assignmentId = assignment.id
      this.isFormativeAssignment = assignment.formative
      this.ownsAllQuestions = assignment.owns_all_questions
      editAssignmentProperties(assignment, this)
    },
    populateFormWithAssignmentTemplate (assignmentProperties) {
      assignmentProperties.is_template = true
      assignmentProperties.modal_already_shown = true
      editAssignmentProperties(assignmentProperties, this)
      this.$forceUpdate()
    },
    async getImportableAssignments (course) {
      let url
      switch (this.collection) {
        case ('my_courses'):
          url = `/api/assignments/courses/${course}`
          break
        case ('all_public_courses'):
          url = `/api/assignments/courses/public/${course}/names`
          break
        case ('commons'):
          url = `/api/assignments/open/commons/${course}`
          break
        default:
          alert(`${this.collection} does not exist.  Please contact us.`)
          return false
      }
      try {
        const { data } = await axios.get(url)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }

        for (let i = 0; i < data.assignments.length; i++) {
          this.importableAssignmentOptions = [{ value: null, text: `Please choose an assignment` }]
          if (data.assignments) {
            for (let i = 0; i < data.assignments.length; i++) {
              this.importableAssignmentOptions.push({ value: data.assignments[i].id, text: data.assignments[i].name })
            }
          }
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async getCoursesByCollection (collection) {
      if (!collection) {
        this.$noty.info('Please first choose a collection')
        return false
      }
      try {
        let url
        let collectionName
        switch (collection) {
          case ('commons'):
            url = '/api/courses/commons'
            collectionName = 'commons_courses'
            break
          case ('my_courses'):
            collectionName = 'courses'
            url = '/api/courses'
            break
          case ('all_public_courses'):
            collectionName = 'public_courses'
            url = '/api/courses/public'
            break
        }

        const { data } = await axios.get(url)
        if (data.type !== 'success') {
          this.$noty.error(data.message)
          return false
        }
        this.importableCourseOptions = [{ value: null, text: `Please choose a course` }]
        if (data[collectionName]) {
          let importableCourseOptions = []
          for (let i = 0; i < data[collectionName].length; i++) {
            let course = data[collectionName][i]
            let text = course.name
            if (collection === 'all_public_courses' && course.instructor === 'Commons Instructor') {
              continue
            }
            if (collection === 'all_public_courses') {
              text += ` --- ${course.instructor}`
              if (course.school !== 'Not Specified') {
                text += `/${course.school}`
              }
            }
            importableCourseOptions.push({ value: course.id, text: text })
          }
          importableCourseOptions.sort((a, b) => (a.text.toUpperCase() > b.text.toUpperCase()) ? 1 : -1)
          this.importableCourseOptions = this.importableCourseOptions.concat(importableCourseOptions)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    updateView () {
      this.view = this.view === 'main view'
        ? 'control panel'
        : 'main view'
      if (this.view === 'main view') {
        this.getAssignments()
      }
    },
    showExternalAssignmentNoty () {
      this.$noty.info('This assignment has no questions to view because it is an external assignment.  To add questions, please edit the assignment and change the Source to ADAPT.')
      return false
    },
    addUntetheredAssignment () {
      this.$bvModal.hide('modal-confirm-add-untethered-assignment')
      this.addAssignmentIsImport
        ? this.initImportAssignment()
        : this.initAddAssignment(this.form, this.courseId, this.assignmentGroups, this.$noty, this.$moment, this.course.start_date, this.course.end_date, this.$bvModal, this.assignmentId)
    },
    confirmInitAddAssignment () {
      this.assignmentId = 0
      this.ownsAllQuestions = true
      this.hasSubmissionsOrFileSubmissions = false
      this.isBetaCourse
        ? this.$bvModal.show('modal-confirm-add-untethered-assignment')
        : this.initAddAssignment(this.form, this.courseId, this.assignmentGroups, this.$noty, this.$moment, this.course.start_date, this.course.end_date, this.$bvModal, this.assignmentId)
    },
    confirmInitImportAssignment () {
      this.isBetaCourse
        ? this.$bvModal.show('modal-confirm-add-untethered-assignment')
        : this.initImportAssignment()
    },
    async getAssignmentGroupFilter (courseId) {
      try {
        const { data } = await axios.get(`/api/assignmentGroups/get-assignment-group-filter/${courseId}`)
        if (data.type === 'success') {
          this.chosenAssignmentGroup = data.assignment_group_filter
          console.log(this.assignmentGroupOptions)
        }
      } catch (error) {
        console.log(error)
      }
    },
    async handleSubmitAssignmentInfo () {
      this.savingAssignment = true
      this.prepareForm(this.form)
      try {
        this.form.course_id = this.courseId
        const { data } = !this.assignmentId
          ? await this.form.post(`/api/assignments`)
          : await this.form.patch(`/api/assignments/${this.assignmentId}`)
        let timeout = data.timeout ? data.timeout : 4000
        this.$noty[data.type](data.message, { timeout: timeout })
        if (data.type === 'success') {
          this.savingAssignment = false
          this.$bvModal.hide('modal-assignment-properties')
          await this.getAssignments()
        }
      } catch (error) {
        this.savingAssignment = false
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          fixInvalid()
          this.allFormErrors = this.form.errors.flatten()
          this.$bvModal.show('modal-form-errors-assignment-form')
        }
      }
    },
    viewAssignTos (assignTosToView) {
      this.assignTosToView = assignTosToView
      this.$bvModal.show('modal-assign-tos-to-view')
    },
    async saveNewOrder () {
      let orderedAssignments = []
      for (let i = 0; i < this.assignments.length; i++) {
        orderedAssignments.push(this.assignments[i].id)
      }

      let noChange = true
      for (let i = 0; i < this.currentOrderedAssignments.length; i++) {
        if (this.currentOrderedAssignments[i] !== this.assignments[i]) {
          noChange = false
        }
      }
      if (noChange) {
        return false
      }
      try {
        const { data } = await axios.patch(`/api/assignments/${this.courseId}/order`, { ordered_assignments: orderedAssignments })
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          for (let i = 0; i < this.assignments.length; i++) {
            this.assignments[i].order = i + 1
          }
          this.currentOrderedAssignments = this.assignments
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async handleImportAssignment (bvEvt) {
      bvEvt.preventDefault()
      if (!this.importableAssignment) {
        this.$noty.info('Please choose an assignment.')
      } else {
        try {
          const { data } = await axios.post(`/api/assignments/import/${this.importableAssignment}/to/${this.courseId}`,
            {
              level: this.importAssignmentForm.level,
              lms_grade_passback: this.importAssignmentForm.lms_grade_passback
            })
          this.$noty[data.type](data.message)
          if (data.type === 'error') {
            return false
          }
          this.getAssignments()
          this.$bvModal.hide('modal-import-assignment')
        } catch (error) {
          this.$noty.error(error.message)
        }
      }
    },
    async initImportAssignment () {
      try {
        const { data } = await axios.get(`/api/assignments/importable-by-user/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.allAssignments = data.all_assignments
        this.$bvModal.show('modal-import-assignment')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initCreateAssignmentFromTemplate (assignmentId) {
      this.createAssignmentFromTemplateAssignmentId = assignmentId
      this.$bvModal.show('modal-create-assignment-from-template')
    },
    async handleCreateAssignmentFromTemplate () {
      try {
        const { data } = await this.createAssignmentFromTemplateForm.post(`/api/assignments/${this.createAssignmentFromTemplateAssignmentId}/create-assignment-from-template`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.getAssignments()
          this.$bvModal.hide('modal-create-assignment-from-template')
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    getGradeBook () {
      this.$router.push(`/courses/${this.courseId}/gradebook`)
    },
    getLockedQuestionsMessage (assignment) {
      if ((Number(assignment.has_submissions_or_file_submissions))) {
        return this.isLockedMessage()
      }
    },
    async getCourseInfo () {
      try {
        const { data } = await axios.get(`/api/courses/${this.courseId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        this.title = `${data.course.name} Assignments`
        this.course = data.course
        console.log(this.course)
        this.betaCoursesInfo = this.course.beta_courses_info
        this.isBetaCourse = this.course.is_beta_course
        this.lms = this.course.lms
        const courseStartDate = this.$moment(this.course.start_date, 'YYYY-MM-DD')
        const november132023 = this.$moment('2023-11-13', 'YYYY-MM-DD')
        this.enableCanvasAPI = courseStartDate.isAfter(november132023)

        this.lmsCourseOptions = [{ value: 0, text: 'Please choose a course' }]
        if (this.course.lms_courses.length) {
          for (let i = 0; i < this.course.lms_courses.length; i++) {
            let lmsCourse = this.course.lms_courses[i]
            this.lmsCourseOptions.push({ text: lmsCourse.name, value: lmsCourse.id })
          }
        }
        console.log(data)
      } catch (error) {
        this.$noty.error(error.message)
        return false
      }
      return true
    },
    async submitShowAssignment (assignment) {
      try {
        const { data } = await axios.patch(`/api/assignments/${assignment.id}/show-assignment/${Number(assignment.shown)}`)
        this.$noty[data.type](data.message)
        if (data.type === 'error') {
          return false
        }
        assignment.shown = !assignment.shown
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    getSubmissionFileView (assignmentId, submissionFiles) {
      if (submissionFiles === 0) {
        this.$noty.info('If you would like students to upload files as part of the assignment, please edit this assignment.')
        return false
      }
      this.$router.push(`/assignments/${assignmentId}/grading`)
    },
    async handleDeleteAssignment () {
      try {
        const { data } = await axios.delete(`/api/assignments/${this.assignmentId}`)
        this.$noty[data.type](data.message)
        await this.resetAll('modal-delete-assignment')
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    deleteAssignment (assignment) {
      if (assignment.is_beta_assignment) {
        this.$bvModal.show('modal-cannot-delete-beta-assignment')
        return false
      }
      this.assignmentId = assignment.id
      this.tetheredBetaAssignmentExists = assignment.tethered_beta_assignment_exists && assignment.id !== 1389
      this.$bvModal.show('modal-delete-assignment')
    },
    async resetAll (modalId) {
      await this.getAssignments()
      // Hide the modal manually
      this.$nextTick(() => {
        this.$bvModal.hide(modalId)
      })
    },
    resetAssignmentGroupForm () {
      this.assignmentGroupForm.errors.clear()
      this.assignmentGroupForm.assignment_group = ''
    },
    metaInfo () {
      return { title: this.$t('home') }
    }
  }
}
</script>
<style scoped></style>
<style>
svg:focus, svg:active:focus {
  outline: none !important;
}

.header-high-z-index table thead tr th {
  z-index: 5 !important;
  border-top: 1px !important; /*gets rid of the flickering issue at top when scrolling.*/
}
</style>
