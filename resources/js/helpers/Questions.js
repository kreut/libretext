export function getSrc(question, jwt_token){
  let src
  switch (question.technology){
    case 'h5p':
      let url = (window.location.host.includes('adapt.libretexts.org') && !window.location.host.includes('dev')) ? 'h5p.libretexts.org' : 'dev.h5p.libretexts.org'
      url = 'dev.h5p.libretexts.org'
      src = `https://${url}/wp-admin/admin-ajax.php?action=h5p_embed&id=${question.technology_id}`
      break;
    case 'webwork':

      src = `https://demo.webwork.rochester.edu/webwork2/html2xml?answersSubmitted=0&sourceFilePath=Library/${question.technology_id}&problemSeed=1234567&courseID=daemon_course&userID=daemon&course_password=daemon&showSummary=1&displayMode=MathJax&language=en&outputformat=libretexts`
      break;
  }
  src += `&jwt_token=${jwt_token}`
  return src
}
/*

URL:  webwork.libretexts.org                                      demo.webwork.rochester.edu
courseID:    anonymous     daemon_course
userID:       anonymous                                               daemon
course_password: anonymous                                   daemon */
