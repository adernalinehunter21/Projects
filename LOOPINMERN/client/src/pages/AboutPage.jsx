import React from 'react';

const AboutPage = () => {
  return (
    <div className="about-page">
      This is the LOOP IN Website which gives the users surveys questions to
      fill and the answers are displayed in the piechart. Then this website also
      allows users to create survey questions and the options are also asked to
      be entered for the questions and the users are allowed to atleast two
      options for a particular survey question. Then after the user answers the
      survey questions, the user who created the survey questions is not allowed
      to answer the questions. The reason the user is not allowed to answer the
      questions is that the user cannot change his answers to the survey
      questions that have been displayed. Also the users have the ability
      surveys of the domains of their interests and gather insight about what
      the audience thinks about the questions in the survey so that the
      moderators who have created the survey questions will be able to be learn
      about the quality and value of their products/services that they are
      offering to the audience.
      <div className="content-div">
        As mentioned above in the introduction of the website the audience
        answers to the survey questions are displayed in the form of piechart so
        that the moderators of the survey questions can be notified in a better
        manner about the sentiment of the users about their products and
        services that are being offered to the audience by the third party
        companies who have created the survey questions.
      </div>
    </div>
  );
};

export default AboutPage;

// export default const AboutPage = () => {
//   return (
//     <div className="about-page">
//         This is the LOOP IN Website which gives the users surveys questions to fill and the answers are displayed in the piechart.
//         Then this website also allows users to create survey questions and the options are also asked to be entered for the questions and the users are allowed to atleast two options for a particular survey question.
//         Then after the user answers the survey questions, the user who created the survey questions is not allowed to answer the questions.
//         The reason the user is not allowed to answer the questions is that the user cannot change his answers to the survey questions that have been displayed.
//     </div>
//   )
// }
