import axios from 'axios'

export async function loginAsStudentInCourse (studentUserId) {
  try {
    const { data } = await axios.post('/api/user/login-as-student-in-course',
      {
        course_id: this.courseId,
        student_user_id: studentUserId
      })

    if (data.type === 'success') {
      // Save the token.
      this.$store.dispatch('auth/saveToken', {
        token: data.token,
        remember: false
      })

      // Fetch the user.
      await this.$store.dispatch('auth/fetchUser')
      // Redirect to the correct home page
      await this.$router.push({ name: 'students.assignments.index' })
    } else {
      this.$noty.error(data.message)// no access
    }
  } catch (error) {
    this.$noty.error(error.message)
  }
}
