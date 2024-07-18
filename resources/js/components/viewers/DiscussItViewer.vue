<template>
  <div>
    {{ discussItSettingsForm }}
   a{{humanReadableTimeUntilRequirementSatisfied }}b

  c{{millisecondsTimeUntilRequirementSatisfied}}d
    <AllFormErrors :modal-id="'modal-form-errors-comments'" :all-form-errors="allFormErrors"/>
    <AllFormErrors :modal-id="'modal-form-errors-discussion-settings'" :all-form-errors="allFormErrors"/>
    <b-modal id="modal-discussion-comment-submission-not-accepted"
             title="Submission Not Accepted"
             size="lg"
             hide-footer
             no-close-on-backdrop
    >
      <b-alert show variant="danger">
        {{ discussionCommentSubmissionResultsError }}
      </b-alert>
      <template #modal-footer>
        <b-button size="sm"
                  variant="primary"
                  @click="$bvModal.hide('modal-discussion-comment-submission-not-accepted')"
        >
          OK
        </b-button>
      </template>
    </b-modal>
    <b-modal id="modal-discussion-comment-submission-accepted"
             title="Submission Accepted"
             size="lg"
             no-close-on-backdrop
    >
      <table class="table table-striped">
        <thead>
        <tr>
          <th scope="col">
            Description
          </th>
          <th scope="col">
            Minimum Number Required
          </th>
          <th scope="col">
            Number Submitted
          </th>
          <th scope="col">
            Requirement Satisfied
          </th>
        </tr>
        </thead>
        <tr
          v-show="completionRequirements.find(item => item.key === 'min_number_of_discussion_threads')"
          :class="discussionCommentSubmissionResults.satisfied_min_number_of_discussion_threads_requirement ? 'text-success' : 'text-danger'"
        >
          <th>Discussion Threads</th>
          <th>{{ discussionCommentSubmissionResults.min_number_of_discussion_threads }}</th>
          <th>{{ discussionCommentSubmissionResults.number_of_discussion_threads_participated_in }}</th>
          <th>
            {{
              discussionCommentSubmissionResults.satisfied_min_number_of_discussion_threads_requirement ? 'Yes' : 'No'
            }}
          </th>
        </tr>
        <tr
          v-show="completionRequirements.find(item => item.key === 'min_number_of_comments')"
          :class="discussionCommentSubmissionResults.satisfied_min_number_of_comments_requirement ? 'text-success' : 'text-danger'"
        >
          <th>Comments</th>
          <th>
            {{
              discussionCommentSubmissionResults.min_number_of_comments_required
            }}
          </th>
          <th>{{ discussionCommentSubmissionResults.number_of_comments_submitted }}</th>
          <th>
            {{
              discussionCommentSubmissionResults.satisfied_min_number_of_comments_requirement ? 'Yes' : 'No'
            }}
          </th>
        </tr>
      </table>
      <template #modal-footer>
        <b-button size="sm"
                  variant="primary"
                  @click="$bvModal.hide('modal-discussion-comment-submission-accepted')"
        >
          OK
        </b-button>
      </template>
    </b-modal>
    <b-modal id="modal-update-text-comment"
             :title="`Comment created by ${activeDiscussionComment.created_by_name} on ${activeDiscussionComment.created_at}`"
             size="lg"
             no-close-on-backdrop
    >
      <DiscussItSatisfiesRequirement :min-number-of-words="+minNumberOfWords"
                                     :comment-form-text="commentForm.text"
                                     :comment-type="'text'"
      />

      <ckeditor
        id="discuss_it_text"
        ref="discuss_it_text"
        v-model="commentForm.text"
        tabindex="0"
        required
        :config="discussItEditorConfig"
        :class="{ 'is-invalid': commentForm.errors.has('text')}"
        class="mb-2"
        @namespaceloaded="onCKEditorNamespaceLoaded"
        @ready="handleFixCKEditor()"
        @keydown="commentForm.errors.clear('text')"
      />
      <template #modal-footer>
        <b-button size="sm"
                  @click="$bvModal.hide('modal-update-text-comment')"
        >
          Cancel
        </b-button>
        <b-button size="sm"
                  variant="primary"
                  @click="saveComment()"
        >
          Save
        </b-button>
      </template>
    </b-modal>
    <b-modal id="modal-confirm-delete-comment"
             title="Confirm Delete Comment"
             size="lg"
             no-close-on-backdrop
    >
      {{activeDiscussionComment}}
      <p>Please confirm that would like to delete the comment {{ confirmDeleteCommentText }}:</p>
      <div v-show="activeDiscussionComment.text" v-html="activeDiscussionComment.text">
      </div>
      <div v-if="activeDiscussionComment.file">
        <iframe
          v-resize="{ log: false }"
          :src="`/discussion-comments/media-player/filename/${activeDiscussionComment.file}`"
          width="100%"
          frameborder="0"
          allowfullscreen=""
        />
      </div>
      <template #modal-footer>
        <b-button size="sm"
                  @click="$bvModal.hide('modal-confirm-delete-comment')"
        >
          Cancel
        </b-button>
        <b-button size="sm"
                  variant="danger"
                  @click="deleteComment()"
        >
          Delete
        </b-button>
      </template>
    </b-modal>
    <b-modal id="modal-listen-or-view-comment"
             :title="`Comment by ${activeDiscussionComment.created_by_name} at ${activeDiscussionComment.created_at}`"
             size="lg"
             no-close-on-backdrop
             :hide-footer="reRecording && !stoppedAudioRecording"
    >
      <iframe v-if="!reRecording"
              v-resize="{ log: false }"
              :src="`/discussion-comments/media-player/discussion-comment-id/${activeDiscussionComment.id}`"
              width="100%"
              frameborder="0"
              allowfullscreen=""
      />
      <DiscussItSatisfiesRequirement v-if="reRecording"
                                     ref="newDiscussionCommentAudio"
                                     :key="`newDiscussionCommentAudio-${discussItSatisfiesRequirementKey}`"
                                     :milliseconds-time-until-requirement-satisfied="millisecondsTimeUntilRequirementSatisfied"
                                     :human-readable-time-until-requirement-satisfied="humanReadableTimeUntilRequirementSatisfied"
                                     :comment-type="'audio'"
                                     :show-satisfies-requirement-timer="showSatisfiesRequirementTimer"
      />

      <div v-if="commentType === 'audio'">
        <audio-recorder
          v-if="reRecording"
          id="discuss-it-recorder"
          ref="recorder"
          :upload-url="`/api/discussion-comments/assignment/${assignmentId}/question/${questionId}/audio`"
          :attempts="1"
          :time="3"
          class="m-auto"
          :show-download-button="false"
          :after-recording="afterRecording"
          :before-recording="beforeRecording"
          :successful-upload="successfulRecordingUpload"
          :failed-upload="failedRecordingUpload"
          :mic-failed="micFailed"
        />
      </div>
      <div v-if="commentType === 'video'">
        <WebCam v-if="reRecording"
                key="update-video-comment"
                :assignment-id="+assignmentId"
                :active-discussion-comment="activeDiscussionComment"
                @saveComment="saveComment"
                @startVideoRecording="startVideoRecording"
                @stopVideoRecording="stopVideoRecording"
        />
      </div>
      <template #modal-footer>
        <span v-show="commentType === 'video' || commentType === 'audio' && !stoppedAudioRecording">
          <b-button v-if="showAction('editComment', activeDiscussionComment.created_by_user_id)" variant="danger"
                    size="sm"
                    @click="reRecording=true"
          >
            Re-record
          </b-button>
          <b-button size="sm" variant="primary"
                    @click="$bvModal.hide('modal-listen-or-view-comment')"
          >
            OK
          </b-button>
        </span>
        <span v-show="commentType === 'audio' && stoppedAudioRecording">
          <span class="mr-2">
            <b-button
              variant="primary"
              size="sm"
              @click="saveAudio"
            >
              Save
            </b-button>
          </span>
          <b-button
            size="sm"
            variant="outline-danger"
            @click="reRecordAudio"
          >
            Delete and re-record
          </b-button>
        </span>
      </template>
    </b-modal>
    <b-modal id="modal-discuss-it-settings"
             title="Settings"
             size="lg"
             no-close-on-backdrop
             @shown="initDiscussItSettings"
    >
      <b-container>
        <b-card header="default" header-html="<h2 class='h7'>Student Actions</h2>">
          <b-card-text>
            <b-form-group
              label-cols-sm="5"
              label-cols-lg="4"
              label-for="students_can_edit_comments"
              label="Students can edit comments"
              label-size="sm"
              label-align="right"
            >
              <template #label>
                Students can edit comments
                <QuestionCircleTooltip id="edit-comments-tooltip"/>
                <b-tooltip target="edit-comments-tooltip"
                           delay="250"
                           triggers="hover focus"
                >
                  With this option enabled, students can edit their comments if the assignment is open. Instructors can
                  edit
                  comments
                  at any time.
                </b-tooltip>
              </template>
              <b-form-radio-group
                id="students_can_edit_comments"
                v-model="discussItSettingsForm.students_can_edit_comments"
                class="pt-1"
                @change="discussItSettingsForm.errors.clear('students_can_edit_comments')"
              >
                <b-form-radio name="students_can_edit_comments" value="1">
                  Yes
                </b-form-radio>
                <b-form-radio name="students_can_edit_comments" value="0">
                  No
                </b-form-radio>
              </b-form-radio-group>
              <ErrorMessage :message="discussItSettingsForm.errors.get('students_can_edit_comments')"/>
            </b-form-group>
            <b-form-group
              label-cols-sm="5"
              label-cols-lg="4"
              label-for="students_can_delete_comments"
              label-size="sm"
              label-align="right"
            >
              <template #label>
                Students can delete comments
                <QuestionCircleTooltip id="delete-comments-tooltip"/>
                <b-tooltip target="delete-comments-tooltip"
                           delay="250"
                           triggers="hover focus"
                >
                  With this option enabled, students can delete their comments if the assignment is open. Instructors
                  can
                  delete comments
                  at any time.
                </b-tooltip>
              </template>
              <b-form-radio-group
                id="students_can_delete_comments"
                v-model="discussItSettingsForm.students_can_delete_comments"
                class="pt-1"
                @change="discussItSettingsForm.errors.clear('students_can_delete_comments')"
              >
                <b-form-radio name="students_can_delete_comments" value="1">
                  Yes
                </b-form-radio>
                <b-form-radio name="students_can_delete_comments" value="0">
                  No
                </b-form-radio>
              </b-form-radio-group>
              <ErrorMessage :message="discussItSettingsForm.errors.get('students_can_delete_comments')"/>
              <has-error :form="discussItSettingsForm" field="students_can_delete_comments"/>
            </b-form-group>
          </b-card-text>
        </b-card>
        <b-card header="default" header-html="<h2 class='h7'>Completion Criteria</h2>" class="pt-3">
          <b-card-text>
            <b-form-group
              label-cols-sm="5"
              label-cols-lg="4"
              label-for="number_of_discussion_threads"
              label-size="sm"
              label-align="right"
            >
              <template #label>
                Participate in at least
                <QuestionCircleTooltip :id="'discussion-thread-tooltip'"/>
                <b-tooltip target="discussion-thread-tooltip" triggers="hover focus" delay="500">
                  A discussion thread can be started by either you or any student. Students then add text or audio/video
                  in the form of
                  comments which are all related to a particular discussion.
                </b-tooltip>
              </template>
              <b-input-group
                :append="+discussItSettingsForm.min_number_of_discussion_threads === 1
                  ? 'discussion thread' : 'discussion threads'"
                size="sm"

                style="width:187px"
              >
                <b-form-input
                  v-model="discussItSettingsForm.min_number_of_discussion_threads"
                  :class="{ 'is-invalid': discussItSettingsForm.errors.has('min_number_of_discussion_threads') }"
                  @keydown="discussItSettingsForm.errors.clear('min_number_of_discussion_threads')"
                />
              </b-input-group>
              <ErrorMessage
                :message="discussItSettingsForm.errors.get('min_number_of_discussion_threads')"
              />
            </b-form-group>
            <b-form-group
              label-cols-sm="5"
              label-cols-lg="4"
              label-for="number_of_comments"
              label-size="sm"
              label-align="right"
            >
              <template #label>
                Submit at least
                <QuestionCircleTooltip :id="'discussion-thread-tooltip'"/>
                <b-tooltip target="discussion-thread-tooltip" triggers="hover focus" delay="500">
                  You or any of your students can submit comments. Each comment can be text or audio/video. Comments are
                  then grouped
                  within discussion threads.
                </b-tooltip>
              </template>
              <b-input-group
                :append="+discussItSettingsForm.min_number_of_comments === 1 ?'comment' : 'comments'"
                size="sm"

                style="width:125px"
              >
                <b-form-input v-model="discussItSettingsForm.min_number_of_comments"
                              :class="{ 'is-invalid': discussItSettingsForm.errors.has('min_number_of_comments') }"
                              @keydown="discussItSettingsForm.errors.clear('min_number_of_comments')"
                />
              </b-input-group>
              <ErrorMessage :message="discussItSettingsForm.errors.get('min_number_of_comments')"/>
            </b-form-group>
            <div class="pb-2">
              A comment will receive credit towards completion if either:
            </div>
            <b-form-group
              label-cols-sm="5"
              label-cols-lg="4"
              label="Text is at least"
              label-size="sm"
              label-align="right"
            >
              <b-input-group
                :append="+discussItSettingsForm.min_number_of_words === 1 ?'word' : 'words'"
                size="sm"

                style="width:125px"
              >
                <b-form-input v-model="discussItSettingsForm.min_number_of_words"
                              :class="{ 'is-invalid': discussItSettingsForm.errors.has('min_number_of_words') }"
                              @keydown="discussItSettingsForm.errors.clear('min_number_of_words')"
                />
              </b-input-group>
              <ErrorMessage :message="discussItSettingsForm.errors.get('min_number_of_words')"/>
            </b-form-group>
            <b-form-group
              label-cols-sm="5"
              label-cols-lg="4"
              label-for="number_of_comments"
              label="Audio/Video is at least"
              label-size="sm"
              label-align="right"
            >
              <b-form-input v-model="discussItSettingsForm.min_length_of_audio_video"
                            size="sm"
                            placeholder="Ex. 1 minute"
                            style="width:200px"
                            :class="{ 'is-invalid': discussItSettingsForm.errors.has('min_length_of_audio_video') }"
                            @keydown="discussItSettingsForm.errors.clear('min_length_of_audio_video')"
              />
              <ErrorMessage :message="discussItSettingsForm.errors.get('min_length_of_audio_video')"/>
            </b-form-group>
            <b-form-radio-group v-model="discussItSettingsForm.auto_grade"
                                class="mb-2"
            >
              <label class="col-sm-5 col-lg-4 col-form-label col-form-label-sm text-right">Auto-grade
                <QuestionCircleTooltip :id="'autograde-tooltip'"/>
                <b-tooltip target="autograde-tooltip" triggers="hover focus" delay="500">
                  If you choose "yes", once a student completes the criteria above, they will get full credit for the
                  question.
                </b-tooltip>
              </label>
              <b-form-radio name="auto-grade-discuss-it" value="1">
                Yes
              </b-form-radio>
              <b-form-radio name="auto-grade-discuss-it" value="0">
                No
              </b-form-radio>
            </b-form-radio-group>
          </b-card-text>
        </b-card>
      </b-container>
      <template #modal-footer="{ ok }">
        <b-button size="sm"
                  @click="$bvModal.hide(`modal-discuss-it-settings`)"
        >
          Cancel
        </b-button>
        <b-button size="sm" variant="primary"
                  @click="saveDiscussItSettings"
        >
          Save
        </b-button>
      </template>
    </b-modal>
    <b-modal id="modal-discuss-it-instructions"
             title="Instructions"
             size="lg"
             no-close-on-backdrop
    >
      <div v-html="qtiJson.prompt"/>
      <template #modal-footer="{ ok }">
        <b-button size="sm" variant="primary"
                  @click="$bvModal.hide(`modal-discuss-it-instructions`)"
        >
          OK
        </b-button>
      </template>
    </b-modal>
    <b-modal id="modal-new-comment"
             :title="activeDiscussion.id ? 'New Comment' : 'New Discussion Thread'"
             no-close-on-backdrop
             size="lg"
             @shown="updateModalToggleIndex('modal-new-comment')"
    >
      {{ commentForm.text }}
      <b-form-group>
        <b-form-radio-group
          v-model="commentType"
          stacked
          required
          label="Comment Type:"
        >
          <b-form-radio name="comment-type" value="text">
            Text
          </b-form-radio>
          <b-form-radio name="comment-type" value="audio">
            Audio
          </b-form-radio>
          <b-form-radio name="comment-type" value="video">
            Video
          </b-form-radio>
        </b-form-radio-group>
      </b-form-group>
      <div v-if="commentType === 'text'">
        <DiscussItSatisfiesRequirement :min-number-of-words="+minNumberOfWords"
                                       :comment-form-text="commentForm.text"
                                       :comment-type="'text'"
        />
        <ckeditor
          id="discuss_it_text"
          ref="discuss_it_text"
          v-model="commentForm.text"
          tabindex="0"
          required
          :config="discussItEditorConfig"
          :class="{ 'is-invalid': commentForm.errors.has('text')}"
          class="mb-2"
          @namespaceloaded="onCKEditorNamespaceLoaded"
          @ready="handleFixCKEditor()"
          @keydown="commentForm.errors.clear('text')"
        />
      </div>
      <div v-if="commentType === 'audio'">
        <div v-if="!discussionCommentAudio">
          <DiscussItSatisfiesRequirement ref="newDiscussionCommentAudio"
                                         :key="`newDiscussionCommentAudio-${discussItSatisfiesRequirementKey}`"
                                         :milliseconds-time-until-requirement-satisfied="millisecondsTimeUntilRequirementSatisfied"
                                         :human-readable-time-until-requirement-satisfied="humanReadableTimeUntilRequirementSatisfied"
                                         :comment-type="'audio'"
                                         :show-satisfies-requirement-timer="showSatisfiesRequirementTimer"
          />
          <audio-recorder
            id="discuss-it-recorder"
            ref="recorder"
            :upload-url="`/api/discussion-comments/assignment/${assignmentId}/question/${questionId}/audio`"
            :attempts="1"
            :time="3"
            class="m-auto"
            :show-download-button="false"
            :after-recording="afterRecording"
            :before-recording="beforeRecording"
            :successful-upload="successfulRecordingUpload"
            :failed-upload="failedRecordingUpload"
            :mic-failed="micFailed"
          />
        </div>
        <div v-if="discussionCommentAudio">
          <iframe v-resize="{ log: false }"
                  :src="`/discussion-comments/media-player/discussion-comment-id/${activeDiscussionCommentId}`"
                  width="100%"
                  frameborder="0"
                  allowfullscreen=""
          />
          <div class="pb-4">
            <b-button v-if="false"
                      class="float-right mr-2"
                      size="sm"
                      variant="danger"
                      @click="confirmDeleteComment('audio')"
            >
              Delete
            </b-button>
          </div>
        </div>
      </div>
      <div v-show="commentType === 'video'">
        <DiscussItSatisfiesRequirement v-if="!discussionCommentVideo"
                                       ref="newDiscussionCommentAudio"
                                       :key="`newDiscussionCommentAudio-${discussItSatisfiesRequirementKey}`"
                                       :milliseconds-time-until-requirement-satisfied="millisecondsTimeUntilRequirementSatisfied"
                                       :human-readable-time-until-requirement-satisfied="humanReadableTimeUntilRequirementSatisfied"
                                       :comment-type="'audio'"
                                       :show-satisfies-requirement-timer="showSatisfiesRequirementTimer"
        />
        <WebCam :key="`new-comment-${commentType}`"
                :assignment-id="+assignmentId"
                @saveComment="saveComment"
                @startVideoRecording="startVideoRecording"
                @stopVideoRecording="stopVideoRecording"
                @updateDiscussionCommentVideo="updateDiscussionCommentVideo"
        />
      </div>
      <template #modal-footer>
        <span v-show="commentType === 'audio' && stoppedAudioRecording">
          <span class="mr-2">
            <b-button
              variant="primary"
              size="sm"
              @click="saveAudio"
            >
              Save
            </b-button>
          </span>
          <b-button
            size="sm"
            variant="outline-danger"
            @click="reRecordAudio"
          >
            Delete and re-record
          </b-button>
        </span>
        <span v-show="commentType === 'text'">
          <b-button size="sm"
                    variant="primary"
                    @click="saveComment()"
          >
            Save
          </b-button>
        </span>
        <b-button
          size="sm"
          class="float-right"
          @click="$bvModal.hide('modal-new-comment')"
        >
          Cancel
        </b-button>
      </template>
    </b-modal>
    <b-container style="font-size:small">
      <b-row>
        <b-col cols="8">
          <div v-if="/\.(mp3|mp4)$/.test(currentDiscussionMedia.s3_key)">
            <iframe
              :key="`discussion-media`"
              v-resize="{ log: false }"
              aria-label="Discussion Media"
              width="100%"
              allowtransparency="true"
              :src="`/question-media-player/${currentDiscussionMedia.s3_key}`"
              frameborder="0"
              allowfullscreen
              class="mb-2"
            />
          </div>
          <div v-if="/\.(pdf)$/.test(currentDiscussionMedia.s3_key)">
            <VuePdfEmbed annotation-layer text-layer :source="currentDiscussionMedia.temporary_url"/>
          </div>

          <b-pagination
            v-model="currentMediaUploadOrder"
            :total-rows="questionMediaUploads.length"
            :per-page="1"
            pills
            align="center"
            first-number
            last-number
            limit="17"
            @change="updateDiscussions($event)"
          />
        </b-col>
        <b-col>
          <div class="mb-2">
            <b-card header="default" header-html="<h2 class='h7'>Submission Information</h2>">
              <ul style="list-style: none">
                <li
                  v-for="(completionRequirement,completionRequirementIndex) in completionRequirements.filter(item =>item.show)"
                  :key="`completion-requirement-${completionRequirementIndex}`"
                >
                  <CompletedIcon :completed="completionRequirement.requirement_satisfied"/>
                  <span :class="completionRequirement.requirement_satisfied ? 'text-success' : 'text-danger'">
                    {{ completionRequirement.text }} <span
                    v-if="completionRequirement.key === 'min_number_of_comments'"
                  >   <QuestionCircleTooltip
                    id="min-number-of-comments-tooltip"
                  />
                      <b-tooltip target="min-number-of-comments-tooltip"
                                 delay="250"
                                 triggers="hover focus"
                      >
                        {{ numberOfCommentsSubmittedMessage }}  <br><br>  {{ completionRequirementsToolTipText }}
                      </b-tooltip></span>
                    <span v-if="completionRequirement.key === 'min_number_of_discussion_threads'">   <QuestionCircleTooltip
                      id="min-number-of-threads-participated-in-tooltip"
                    />
                      <b-tooltip target="min-number-of-threads-participated-in-tooltip"
                                 delay="250"
                                 triggers="hover focus"
                      >
                        {{ numberOfThreadsParticipatedInMessage }}
                      </b-tooltip></span>
                  </span>
                </li>
              </ul>
              <hr v-if="discussionCommentSubmissionResults.submission_summary">
              <ul v-if="discussionCommentSubmissionResults.submission_summary" style="list-style: none;">
                <li>
                  <span class="font-weight-bold">Date Completed:</span>
                  {{ discussionCommentSubmissionResults.submission_summary.date_submitted }}
                </li>
                <li>
                  <span class="font-weight-bold">Date Graded:</span>
                  {{ discussionCommentSubmissionResults.submission_summary.date_graded }}
                </li>
                <li>
                  <span class="font-weight-bold">Score:</span>
                  {{ discussionCommentSubmissionResults.submission_summary.score }}
                </li>
              </ul>
              <div
                v-if="discussionCommentSubmissionResults.submission_summary && discussionCommentSubmissionResults.submission_summary.grader_id"
                class="pr-2"
              >
                <hr>
                <b-button size="sm" variant="outline-primary"
                          @click="$emit('openContactGraderModal','discuss-it')"
                >
                  Contact Grader
                </b-button>
              </div>
            </b-card>
          </div>
          <div v-if="discussions.length">
            <div class="accordion" role="tablist">
              <div class="mb-2">
                <span class="float-right">
                  <b-button variant="outline-info" size="sm" @click="showAllDiscussions">Show All</b-button> <b-button
                  size="sm" @click="hideAllDiscussions"
                >Hide All</b-button>
                </span>
              </div>
              <div class="clearfix mb-2"/>
              <b-card v-for="(discussion, discussionIndex) in discussions" :key="`discussion-${discussionIndex}`"
                      no-body
                      class="mb-1"
              >
                <b-card-header header-tag="header" class="p-1" role="tab">
                  <b-button v-b-toggle="`accordion-${discussionIndex}`" block variant="info">
                    {{ discussion.started_by }} on
                    {{ discussion.created_at }} <span class="float-right"><b-icon-eye
                    v-show="!isOpen(discussionIndex)"
                  /> <b-icon-eye-slash v-show="isOpen(discussionIndex)"/>
                    </span>
                  </b-button>
                </b-card-header>
                <b-collapse :id="`accordion-${discussionIndex}`"
                            role="tabpanel"
                            :visible="isOpen(discussionIndex)"
                            @shown="$set(openStates, discussionIndex, true)"
                            @hidden="$set(openStates, discussionIndex, false)"
                >
                  <b-card-body>
                    <b-card-text>
                      <div v-for="(comment, commentsIndex) in discussion.comments"
                           :key="`comments-${discussion.id}-${commentsIndex}`"
                      >
                        <user-initials
                          v-b-tooltip="{title: `Comment created by ${comment.created_by_name} on ${comment.created_at}`,delay: '500'}"
                          :user-name="comment.created_by_name"
                          :is-user="comment.created_by_user_id === user.id"
                          style="cursor: pointer"
                        />
                        <div v-show="comment.text" v-html="comment.text"/>
                        <span v-show="comment.file">
                          <b-button size="sm" variant="outline-info" @click="listenOrViewComment(comment)"
                          >{{ listenOrViewCommentText(comment) }} Comment</b-button>
                        </span>
                        <a :id="getTooltipTarget('editComment',comment.id)"
                           href=""
                           :aria-label="`Edit comment by ${ comment.created_by_name }, created on ${comment.created_at }`"
                           @click.prevent="initEditComment(comment)"
                        >
                          <span v-if="showAction('editComment',comment.created_by_user_id)"><b-icon-pencil
                            class="font-weight-bold"
                          /></span>
                        </a>
                        <a :id="getTooltipTarget('deleteComment',comment.id)"
                           href=""
                           :aria-label="`Delete comment by ${ comment.created_by_name }, created on ${comment.created_at }`"
                           @click.prevent="initDeleteComment(comment)"
                        >
                          <span v-if="showAction('deleteComment',comment.created_by_user_id)"><b-icon-trash
                            class="font-weight-bold"
                          /></span>
                        </a>
                        <b-tooltip :target="getTooltipTarget('deleteComment',comment.id)"
                                   delay="500"
                                   triggers="hover"
                        >
                          Delete comment by {{ comment.created_by_name }}, created on {{ comment.created_at }}
                        </b-tooltip>
                        <hr>
                      </div>
                      <b-button v-if="canStartDiscussionOrAddComments"
                                size="sm"
                                @click="newComment(discussion)"
                      >
                        New Comment
                      </b-button>
                    </b-card-text>
                  </b-card-body>
                </b-collapse>
              </b-card>
            </div>
          </div>
          <hr v-if="discussions.length && canStartDiscussionOrAddComments">
          <div>
            <b-button v-if="canStartDiscussionOrAddComments"
                      variant="info"
                      class="float-right"
                      @click="startDiscussion"
            >
              New Discussion Thread
            </b-button>
          </div>
        </b-col>
      </b-row>
    </b-container>
  </div>
</template>

<script>
import VuePdfEmbed from 'vue-pdf-embed/dist/vue2-pdf-embed'
import axios from 'axios'
import { mapGetters } from 'vuex'
import Form from 'vform'
import AllFormErrors from '../AllFormErrors.vue'
import ErrorMessage from '../ErrorMessage.vue'
import { getTooltipTarget } from '../../helpers/Tooptips'
import UserInitials from '../UserInitials.vue'
import WebCam from '../WebCam.vue'
import CompletedIcon from '../CompletedIcon.vue'
import DiscussItSatisfiesRequirement from '../DiscussItSatisfiesRequirement.vue'
import { fixCKEditor, updateModalToggleIndex } from '~/helpers/accessibility/fixCKEditor'
import CKEditor from 'ckeditor4-vue'

export default {
  name: 'DiscussItViewer',
  components: {
    DiscussItSatisfiesRequirement,
    CompletedIcon,
    ErrorMessage,
    AllFormErrors,
    UserInitials,
    WebCam,
    VuePdfEmbed,
    ckeditor: CKEditor.component
  },
  props: {
    qtiJson: {
      type: Object,
      default: () => {
      }
    },
    assignmentId: {
      type: Number,
      default: 0
    },
    questionId: {
      type: Number,
      default: 0
    },
    canStartDiscussionOrAddComments: {
      type: Boolean,
      default: true
    }
  },
  data: () => ({
    millisecondsTimeUntilRequirementSatisfied: 0,
    humanReadableTimeUntilRequirementSatisfied: '',
    discussItEditorConfig: {
      toolbar: [
        { name: 'image', items: ['Image'] },
        { name: 'math', items: ['Mathjax'] }
      ],
      // Configure the Enhanced Image plugin to use classes instead of styles and to disable the
      // resizer (because image size is controlled by widget styles or the image takes maximum
      // 100% of the editor width).
      image2_alignClasses: ['image-align-left', 'image-align-center', 'image-align-right'],
      image2_altRequired: true,
      removeButtons: '',
      extraPlugins: 'mathjax,embed,dialog,contextmenu,liststyle,image2,autogrow',
      mathJaxLib: 'https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML',
      embed_provider: '//ckeditor.iframe.ly/api/oembed?url={url}&callback={callback}',
      filebrowserUploadUrl: '/api/ckeditor/upload',
      filebrowserUploadMethod: 'form',
      format_tags: 'p;h2;h3;pre',
      allowedContent: true,
      disableNativeSpellChecker: false
    },
    questionMediaUploads: [],
    discussionCommentSubmissionResultsError: '',
    openStates: [true],
    openIndex: 0,
    discussionCommentVideo: false,
    discussItSatisfiesRequirementKey: 0,
    showSatisfiesRequirementTimer: false,
    minNumberOfWords: 0,
    discussionCommentSubmissionResults: { min_number_of_discussion_threads: '' },
    numberOfThreadsParticipatedInMessage: '',
    numberOfCommentsSubmittedMessage: '',
    completionRequirementsToolTipText: '',
    hasCompletionRequirements: false,
    currentDiscussionMedia: {},
    reRecording: false,
    confirmDeleteCommentText: '',
    discussItSettingsForm: new Form({
      min_number_of_comments: '',
      students_can_edit_comments: '',
      students_can_delete_comments: ''
    }),
    activeDiscussionComment: {},
    activeDiscussionCommentId: 0,
    audioHeaders: {},
    discussionCommentAudio: false,
    stoppedAudioRecording: false,
    showPDF: true,
    activeUserId: 0,
    discussionsByUserId: [],
    activeDiscussion: {},
    allFormErrors: [],
    commentForm: new Form({
      text: '',
      commentType: 'text'
    }),
    commentType: 'text',
    discussions: [],
    currentMediaUploadOrder: 1,
    numPages: 0,
    completionRequirements: []
  }),
  computed: {
    ...mapGetters({
      user: 'auth/user'
    })
  },
  async mounted () {
    if (this.assignmentId && this.questionId) {
      const questionMediaUploads = await this.getMediaUploadsByQuestionId()
      if (questionMediaUploads !== false) {
        this.questionMediaUploads = questionMediaUploads
        await this.$nextTick(() => {
          this.updateDiscussions(1)
          this.getDiscussItSettings()
        })
      }
    }
  },
  methods: {
    updateModalToggleIndex,
    getTooltipTarget,
    setRequirementSatisfied(){
      this.commentForm.requirement_satisfied = true
    },
    handleFixCKEditor () {
      fixCKEditor(this)
    },
    onCKEditorNamespaceLoaded (CKEDITOR) {
      CKEDITOR.addCss('.cke_editable { font-size: 15px; }')
    },
    async getMediaUploadsByQuestionId () {
      try {
        const { data } = await axios.get(`/api/question-media/assignments/${this.assignmentId}/questions/${this.questionId}`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
          return false
        }
        return data.question_media_uploads
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    showAllDiscussions () {
      this.openStates = this.openStates.map(() => true)
    },
    hideAllDiscussions () {
      this.openStates = this.openStates.map(() => false)
    },
    isOpen (index) {
      return this.openStates[index] || false
    },
    updateDiscussionCommentVideo () {
      this.discussionCommentVideo = true
    },
    stopVideoRecording () {
      this.$refs.newDiscussionCommentAudio.endCountDown()
    },
    startVideoRecording () {
      this.discussionCommentVideo = false
      this.showSatisfiesRequirementTimer = true
    },
    async getSatisfiedRequirements () {
      try {
        const { data } = await axios.get(`/api/discussion-comments/assignment/${this.assignmentId}/question/${this.questionId}/user/${this.user.id}/satisfied`)
        if (data.type === 'error') {
          this.$noty.error(data.message)
        } else {
          if (this.completionRequirements.find(item => item.key === 'min_number_of_comments')) {
            this.completionRequirements.find(item => item.key === 'min_number_of_comments').requirement_satisfied = data.satisfied_min_number_of_comments_requirement
            this.numberOfCommentsSubmittedMessage = data.number_of_comments_submitted_message
          }
          if (this.completionRequirements.find(item => item.key === 'min_number_of_discussion_threads')) {
            this.completionRequirements.find(item => item.key === 'min_number_of_discussion_threads').requirement_satisfied = data.satisfied_min_number_of_discussion_threads_requirement
            this.numberOfThreadsParticipatedInMessage = data.number_of_discussion_threads_participated_in_message
          }
          this.discussionCommentSubmissionResults = data
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    initEditComment (comment) {
      this.activeDiscussionComment = comment
      if (comment.text) {
        this.commentForm.text = comment.text
        this.commentForm.type = 'text'
        this.$bvModal.show('modal-update-text-comment')
      } else {
        this.listenOrViewComment(comment)
      }
    },
    async deleteComment () {
      try {
        const { data } = await axios.delete(`/api/discussion-comments/${this.activeDiscussionComment.id}`)
        this.$noty[data.type](data.message)
        if (data.type !== 'error') {
          this.$bvModal.hide('modal-confirm-delete-comment')
          this.activeDiscussionComment = {}
          await this.getDiscussionsByMediaUploadId()
          await this.getSatisfiedRequirements()
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    listenOrViewCommentText (comment) {
      if (comment.file) {
        return comment.file.includes('mp3') ? 'Listen to' : 'View'
      } else {
        return ''
      }
    },
    initDeleteComment (comment) {
      this.activeDiscussionComment = comment
      this.confirmDeleteCommentText = comment.created_by_user_id === this.user.user_id
        ? `you created ${this.activeDiscussionComment.created_at}`
        : `created by ${this.activeDiscussionComment.created_by_name} at ${this.activeDiscussionComment.created_at}`
      this.$bvModal.show('modal-confirm-delete-comment')
    },
    showAction (action, userId) {
      let showAction
      showAction = false
      switch (action) {
        case ('editComment'):
          showAction = (this.canStartDiscussionOrAddComments && this.user.role === 3 && userId === this.user.id && this.discussItSettingsForm.students_can_edit_comments) ||
            this.user.role === 2
          break
        case ('deleteComment'):
          showAction = (this.canStartDiscussionOrAddComments && this.user.role === 3 && userId === this.user.id && this.discussItSettingsForm.students_can_delete_comments) ||
            this.user.role === 2
          break
      }
      return showAction
    },
    initDiscussItSettings () {
      this.discussItSettingsForm.errors.clear()
      this.getDiscussItSettings()
    },
    async getDiscussItSettings () {
      try {
        const { data } = await axios.get(`/api/assignments/${this.assignmentId}/question/${this.questionId}/discuss-it-settings`)
        if (data.type === 'success') {
          this.discussItSettingsForm = new Form(JSON.parse(data.discuss_it_settings))
          this.humanReadableTimeUntilRequirementSatisfied = this.discussItSettingsForm.min_length_of_audio_video
          this.millisecondsTimeUntilRequirementSatisfied = this.discussItSettingsForm.min_length_of_audio_video_in_milliseconds
          this.completionRequirements = [
            { key: 'min_number_of_discussion_threads', requirement_satisfied: false, show: true },
            { key: 'min_number_of_comments', requirement_satisfied: false, show: true },
            { key: 'min_number_of_words', show: false },
            { key: 'min_length_of_audio_video', show: false }
          ]
          this.completionRequirementsToolTipText = ''
          for (let i = 0; i < this.completionRequirements.length; i++) {
            const key = this.completionRequirements[i].key
            const requirementExists = this.discussItSettingsForm[key] !== ''
            this.completionRequirements[i].requirementExists = requirementExists
            if (requirementExists) {
              const value = this.discussItSettingsForm[key]
              const plural = +value === 1 ? '' : 's'
              switch (key) {
                case ('min_number_of_discussion_threads'):
                  this.completionRequirements[i].text = `Participate in at least ${value} discussion thread${plural}.`
                  break
                case ('min_number_of_comments'):
                  this.completionRequirements[i].text = `Submit at least ${value} comment${plural}.`
                  break
                case ('min_number_of_words'):
                  this.completionRequirementsToolTipText += `Text comments need to be at least ${value} word${plural}.  `
                  this.minNumberOfWords = value
                  break
                case ('min_length_of_audio_video'):
                  this.completionRequirementsToolTipText += `Audio/Video comments need to be at least ${value}.`
                  break
              }
            }
          }
          this.completionRequirements = this.completionRequirements.filter(item => item.requirementExists)
          await this.getSatisfiedRequirements()
        } else {
          this.$noty.error(data.message)
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    },
    async saveDiscussItSettings () {
      try {
        const { data } = await this.discussItSettingsForm.patch(`/api/assignments/${this.assignmentId}/question/${this.questionId}/discuss-it-settings`)
        this.$noty[data.type](data.message)
        if (data.type === 'success') {
          this.$bvModal.hide('modal-discuss-it-settings')
        }
        await this.getDiscussItSettings()
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.discussItSettingsForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-discussion-settings')
        }
      }
    },
    listenOrViewComment (comment) {
      this.activeDiscussionComment = comment
      if (comment.file.endsWith('.mp3')) {
        this.commentType = 'audio'
      } else if (comment.file.endsWith('.webm')) {
        this.commentType = 'video'
      } else {
        this.$noty.error('This file does not have an .mp3 or .mp4 extension.')
        return false
      }
      this.$bvModal.show('modal-listen-or-view-comment')
    },
    confirmDeleteComment (test) {

    },
    async saveAudio () {
      if (!document.getElementsByClassName('ar__uploader')[0]) {
        this.$noty.info('Please first record some audio by clicking on the mic icon and then clicking the stop icon when you have completed your recording.')
      } else {
        document.getElementsByClassName('ar__uploader')[0].click()
      }
    },
    reRecordAudio () {
      this.discussionCommentAudio = false
      this.discussItSatisfiesRequirementKey++
      this.showSatisfiesRequirementTimer = false
      try {
        this.$refs.recorder.removeRecord()
      } catch (error) {
        console.log('Does not exist if done multiple times.')
      }
      this.stoppedAudioRecording = false
      this.$noty.info('The recording has been deleted.')
    },
    afterRecording () {
      this.$nextTick(() => {
        document.getElementsByClassName('ar-records__record')[0].click()
      })
      this.stoppedAudioRecording = true
      this.$refs.newDiscussionCommentAudio.endCountDown()
    },
    beforeRecording () {
      this.discussionCommentAudio = false
      this.showSatisfiesRequirementTimer = true
    },
    successfulRecordingUpload (response) {
      const data = response.data
      data.type === 'success' ? this.saveComment(data.file) : this.$noty.error(data.message)
    },
    failedRecordingUpload (response) {
      this.$noty[response.data.type](response.data.message)
    },
    micFailed () {
      this.$noty.error('We are unable to access your mic.')
    },
    updateDiscussions (currentMediaUploadOrder) {
      this.currentDiscussionMedia = this.questionMediaUploads.find(mediaUpload => mediaUpload.order === currentMediaUploadOrder)
      this.$nextTick(() => {
        this.getDiscussionsByMediaUploadId()
      })
    },
    initCommentOrDiscussion (discussion = {}) {
      this.commentType = 'text'
      this.commentForm = new Form({
        text: ''
      })
      this.activeDiscussion = {}
      this.activeDiscussionComment = {}
      this.discussionCommentAudio = false
      this.discussionCommentVideo = false
      this.discussItSatisfiesRequirementKey++
      this.showSatisfiesRequirementTimer = false

      this.activeDiscussion = discussion.id ? discussion : {}
      this.$bvModal.show('modal-new-comment')
    },
    startDiscussion () {
      this.initCommentOrDiscussion()
    },
    newComment (discussion) {
      this.initCommentOrDiscussion(discussion)
    },
    getMediaUploadId () {
      return this.questionMediaUploads.find(mediaUpload => mediaUpload.order === this.currentMediaUploadOrder).id
    },
    async saveComment (file = '') {
      this.reRecording = false
      const mediaUploadId = this.getMediaUploadId()
      const discussionId = this.activeDiscussion.id ? this.activeDiscussion.id : 0
      const action = this.activeDiscussionComment.id ? 'patch' : 'post'
      const url = this.activeDiscussionComment.id
        ? `/api/discussion-comments/${this.activeDiscussionComment.id}`
        : `/api/discussions/assignment/${this.assignmentId}/question/${this.questionId}/${mediaUploadId}/${discussionId}`
      this.commentForm.type = this.commentType
      if (file || ['audio', 'video'].includes(this.commentType)) {
        this.commentForm.file = file
        this.commentForm.type = file ? 'file' : this.commentType
      }

      try {
        this.$bvModal.hide('modal-new-comment')
        this.$bvModal.hide('modal-update-text-comment')
        this.$bvModal.hide('modal-listen-or-view-comment')
        const { data } = await this.commentForm[action](url)
        this.activeDiscussionCommentId = data.discussion_comment_id
        if (data.type !== 'error') {
          await this.getSatisfiedRequirements()
          this.$bvModal.show('modal-discussion-comment-submission-accepted')
          await this.getDiscussionsByMediaUploadId()
        } else {
          this.discussionCommentSubmissionResultsError = data.message
          this.$bvModal.show('modal-discussion-comment-submission-not-accepted')
        }
      } catch (error) {
        if (!error.message.includes('status code 422')) {
          this.$noty.error(error.message)
        } else {
          this.allFormErrors = this.commentForm.errors.flatten()
          this.$bvModal.show('modal-form-errors-comments')
        }
      }
    },
    async getDiscussionsByMediaUploadId () {
      const mediaUploadId = this.getMediaUploadId()
      try {
        const { data } = await axios.get(`/api/discussions/assignment/${this.assignmentId}/question/${this.questionId}/media-upload/${mediaUploadId}`)
        if (['error', 'info'].includes(data.type)) {
          this.$noty[data.type](data.message)
          return false
        }
        this.discussions = data.discussions
        this.$nextTick(() => {
          MathJax.Hub.Queue(['Typeset', MathJax.Hub])
        })
        for (let i = 0; i < this.discussions.length; i++) {
          this.openStates[i] = i === 0
        }
      } catch (error) {
        this.$noty.error(error.message)
      }
    }
  }
}
</script>
