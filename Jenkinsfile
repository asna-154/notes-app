pipeline {
    agent any

    stages {
        stage('Syntax check (Docker agent)') {
            agent {
                docker {
                    image 'asna154/notes-app:latest'
                    reuseNode true
                }
            }
            steps {
                sh 'php -l app/index.php'
            }
        }

        stage('Build') {
            steps {
                sh 'docker compose -p notes-ci -f ci/docker-compose.yml down'
                sh 'docker compose -p notes-ci -f ci/docker-compose.yml up -d'
            }
        }
    }

    post {
        always {
            script {
                sh "git config --global --add safe.directory ${env.WORKSPACE}"
                def committer = sh(
                    script: "git log -1 --pretty=format:'%ae'",
                    returnStdout: true
                ).trim()
                // GitHub "private" commit emails cannot receive mail, so fall back to the instructor
                def recipient = (committer == '' || committer.endsWith('noreply.github.com')) ? 'qasimalik@gmail.com' : committer
                emailext(
                    to: recipient,
                    cc: 'YOUR_GMAIL@gmail.com',
                    subject: "Build #${env.BUILD_NUMBER}: ${currentBuild.currentResult}",
                    body: "Build Status: ${currentBuild.currentResult}\nJob: ${env.JOB_NAME}\nBuild number: ${env.BUILD_NUMBER}\nCommit author: ${committer}\nBuild URL: ${env.BUILD_URL}"
                )
            }
        }
    }
}
