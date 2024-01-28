pipeline {
    agent { label '!windows'}

    stages {
        stage("Build") {
            steps {
                echo "Building application.."
                echo "Build ID is ${BUILD_ID}"
            }
        }
        stage("Test") {
            steps {
                echo "Testing application.."
            }
        }
        stage("Deploy") {
            steps {
                echo "Deploy application"
            }
        }
    }
    post {
        success {
            echo "Build successful"
        }
        failure {
            echo "Build failed, see console for the details"
        }
    }
}