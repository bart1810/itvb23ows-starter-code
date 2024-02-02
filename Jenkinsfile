pipeline {
    agent { label '!windows' }

    stages {
        stage("SonarQube") {
            steps {
                script {
                    scannerHome = tool 'SonarQube Scanner'
                }
                echo "Start Sonar Scanner.."
                withSonarQubeEnv('SonarQube') {
                sh "${scannerHome}/bin/sonar-scanner -Dsonar.projectKey=hive"
                    }
                }
            }
        stage("docker-compose up") {
            steps {
                echo "Building application.."
                echo "Build ID is ${BUILD_ID}"
                sh "docker-compose up --build -d "
                echo "Application build"
            }
        }
        stage("Testen met PHPUnit") {
            steps {
                echo "Moet nog geïmplementeerd worden"
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
        always {
            sh "docker-compose down"
        }
        success {
            echo "Build successful"
        }
        failure {
            echo "Build failed, see console for the details"
        }
    }
}