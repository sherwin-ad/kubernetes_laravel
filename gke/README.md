# Laravel on GKE Setup Guide
Deploying a Laravel application with Apache in Google Kubernetes Engine (GKE) is a robust way to host your application. It involves containerizing your Laravel app with Apache, pushing the image to a container registry, setting up a GKE cluster, configuring your database, defining Kubernetes resources, and finally, deploying your application.

Here's a comprehensive guide, building upon the previous discussions:

**Prerequisites:**
1. Google Cloud Platform (GCP) Account: An active GCP account with billing enabled.
2. Google Cloud SDK (gcloud CLI): Install and initialize the gcloud CLI on your local machine.
3. kubectl: Install the Kubernetes command-line tool.
4. Docker: Install Docker on your local machine to build container images
5. Laravel Application: Have your Laravel project ready.
6. Git: For version control (recommended for your Laravel project).

**Step-by-Step Deployment:**

1. Containerize Your Laravel Application with Apache (Dockerfile)

Create a Dockerfile in the root of your Laravel project. This Dockerfile will build an image containing PHP, Apache, and your Laravel application.

Dockerfile
```
# Stage 1: Build dependencies (using Composer)
FROM composer:2 as composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Stage 2: Final application image with Apache
FROM php:8.2-apache # Or your preferred PHP version with Apache

WORKDIR /var/www/html

# Install system dependencies commonly required by Laravel and PHP extensions
# Update apt and install essential tools, image processing libs, etc.
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libonig-dev \
    libicu-dev \
    libzip-dev \
    # Add any other system libraries your specific Laravel app needs
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required by Laravel
RUN docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    # Add any other PHP extensions your app requires (e.g., redis, opcache)
    && a2enmod rewrite # Enable Apache's mod_rewrite for Laravel's clean URLs
    && a2enmod headers # Enable headers module if needed for CORS etc.

# Copy application code from your host to the container
COPY . .

# Copy composer dependencies from the build stage
COPY --from=composer /app/vendor /var/www/html/vendor

# Configure Apache virtual host to point to Laravel's public directory
# This command uses sed to modify the default Apache site configuration.
# It changes the DocumentRoot from /var/www/html to /var/www/html/public.
# It also ensures that .htaccess files are respected by setting AllowOverride All.
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && echo '<Directory /var/www/html/public>' >> /etc/apache2/apache2.conf \
    && echo '    AllowOverride All' >> /etc/apache2/apache2.conf \
    && echo '</Directory>' >> /etc/apache2/apache2.conf

# Set proper permissions for Laravel storage and cache directories
# This is crucial for Laravel to function correctly (logging, caching, sessions, uploads)
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Optimize Laravel for production (run these during build for smaller, faster images)
# Note: Ensure you don't call `env()` outside config files if using config:cache
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Expose port 80 (Apache's default HTTP port)
EXPOSE 80

# Apache is designed to run in the foreground by default in this base image
# CMD ["apache2-foreground"] # This is usually the default CMD and doesn't need to be explicitly added
```
2. Build and Push Docker Image to Google Container Registry (GCR) or Artifact Registry

- Authenticate Docker with GCP:
```
gcloud auth configure-docker
```

- Build your Docker image:
```
docker build -t gcr.io/<PROJECT_ID>/laravel-apache-app:latest .
# Replace <PROJECT_ID> with your actual GCP Project ID.
```

For production, consider using a specific version tag instead of latest (e.g., v1.0.0).

- Push the image to GCR:
```
docker push gcr.io/<PROJECT_ID>/laravel-apache-app:latest
```
3. Create a GKE Cluster

If you don't have a GKE cluster, create one. You can do this via the GCP Console or gcloud CLI.
```
gcloud container clusters create laravel-apache-cluster \
    --zone=<YOUR_ZONE> \
    --num-nodes=2 \
    --machine-type=e2-medium \
    --release-channel=stable # Recommended for production
# Add other configurations like network policies, private cluster if needed
```

Replace <YOUR_ZONE> with a GCP zone near your users (e.g., us-central1-c, asia-southeast1-a).

- Get cluster credentials (connect kubectl to your cluster):
```
gcloud container clusters get-credentials laravel-apache-cluster --zone=<YOUR_ZONE>
```

4. Configure Database (Google Cloud SQL)

For a robust production setup, use Cloud SQL (e.g., MySQL, PostgreSQL).

- Create a Cloud SQL instance:

  - Go to Navigation menu > SQL in the GCP Console.
  - Click Create instance.
  - Choose your desired database engine (MySQL is common for Laravel).
  - Select a region (ideally the same as your GKE cluster).
  - Set a root password and a database name for your Laravel app.

- Create a Service Account for Cloud SQL Proxy:
  You'll need a service account with the Cloud SQL Client role for your GKE pods to connect to Cloud SQL securely via the Cloud SQL Proxy.

```
gcloud iam service-accounts create cloudsql-proxy-sa --display-name="Cloud SQL Proxy Service Account"
gcloud projects add-iam-policy-binding <PROJECT_ID> \
    --member="serviceAccount:cloudsql-proxy-sa@<PROJECT_ID>.iam.gserviceaccount.com" \
    --role="roles/cloudsql.client"
```
  - Generate and Download JSON Key: Go to IAM & Admin > Service Accounts, find cloudsql-proxy-sa, click the three dots, select Manage keys > Add Key > Create new key > JSON. Save this JSON file securely on your local machine.

5. Define Kubernetes Manifests (YAML Files)

Create the following YAML files in a k8s/ directory within your Laravel project.

- k8s/01-cloudsql-secret.yaml: Store your Cloud SQL Proxy service account key securely as a Kubernetes Secret.

```
# IMPORTANT: Replace /path/to/your/cloudsql-proxy-service-account-key.json
# with the actual path to the JSON key file you downloaded.
kubectl create secret generic cloudsql-proxy-credentials \
  --from-file=credentials.json=/path/to/your/cloudsql-proxy-service-account-key.json \
  --dry-run=client -o yaml > k8s/01-cloudsql-secret.yaml
```
(You can run the kubectl create secret command with --dry-run=client -o yaml to generate the YAML directly, or manually create it by base64 encoding the content of your JSON key.)

- k8s/02-laravel-configmap.yaml: For non-sensitive environment variables.
```
apiVersion: v1
kind: ConfigMap
metadata:
  name: laravel-config
data:
  APP_ENV: production
  APP_DEBUG: "false"
  APP_URL: http://your-domain.com # Update after Ingress IP is known
  DB_CONNECTION: mysql
  # For Cloud SQL Proxy, connect via Unix socket or TCP loopback
  # Using Unix socket requires configuring Laravel's database.php
  DB_HOST: 127.0.0.1 # Cloud SQL Proxy listens on localhost (TCP)
  DB_PORT: "3306"
  DB_DATABASE: your_database_name # Your Cloud SQL database name
  BROADCAST_DRIVER: log
  CACHE_DRIVER: file # Consider 'redis' for better performance
  SESSION_DRIVER: file # Consider 'redis' for better performance
  QUEUE_CONNECTION: sync # Consider 'redis' or 'database' for queues
  # Add other non-sensitive Laravel environment variables here
  # For Laravel logs to go to stdout/stderr (visible in GKE logs), set:
  LOG_CHANNEL: stderr
```
Note: If your Laravel database.php is configured to use a Unix socket for MySQL, set DB_HOST to /cloudsql/<PROJECT_ID>:<REGION>:<INSTANCE_CONNECTION_NAME>. The INSTANCE_CONNECTION_NAME is found in Cloud SQL instance overview (e.g., your-project:your-region:your-instance-name).

- k8s/03-laravel-secrets.yaml: For sensitive environment variables.
```
apiVersion: v1
kind: Secret
metadata:
  name: laravel-secrets
type: Opaque
data:
  # Base64 encode your values: echo -n "your_value" | base64
  APP_KEY: <base64_encoded_app_key> # php artisan key:generate, then base64 encode the output
  DB_USERNAME: <base64_encoded_db_username> # Your Cloud SQL DB username
  DB_PASSWORD: <base64_encoded_db_password> # Your Cloud SQL DB password
  # Add other sensitive Laravel environment variables here
```

- k8s/04-laravel-deployment.yaml: Defines your application's pods.
```
apiVersion: apps/v1
kind: Deployment
metadata:
  name: laravel-apache-app
  labels:
    app: laravel-apache
spec:
  replicas: 2 # Number of instances (pods) for your application
  selector:
    matchLabels:
      app: laravel-apache
  template:
    metadata:
      labels:
        app: laravel-apache
    spec:
      # This links to the service account used by the Cloud SQL Proxy container
      serviceAccountName: cloudsql-proxy-sa
      containers:
      - name: laravel # Your main Laravel application container
        image: gcr.io/<PROJECT_ID>/laravel-apache-app:latest # Your Docker image
        ports:
        - containerPort: 80 # Apache listens on port 80
        envFrom:
        - configMapRef:
            name: laravel-config # Referencing the ConfigMap
        - secretRef:
            name: laravel-secrets # Referencing the Secret
        volumeMounts:
        # Mount the shared volume for Cloud SQL Proxy socket
        - name: cloudsql-proxy-socket
          mountPath: /cloudsql # Proxy will create socket here
        # Optional: If you need persistent storage for uploads etc.
        # - name: laravel-storage
        #   mountPath: /var/www/html/storage/app/public # Or wherever Laravel stores persistent files
        #   subPath: app/public # Use subPath to mount a subdirectory of the PVC
        # Add liveness and readiness probes for health checks
        livenessProbe:
          httpGet:
            path: /health # Or your Laravel health check endpoint (e.g., /up)
            port: 80
          initialDelaySeconds: 30
          periodSeconds: 10
        readinessProbe:
          httpGet:
            path: /health
            port: 80
          initialDelaySeconds: 15
          periodSeconds: 5

      - name: cloudsql-proxy # Sidecar container for secure Cloud SQL connection
        image: gcr.io/cloudsql-docker/gce-proxy:latest
        command: ["/cloud_sql_proxy",
                  "-instances=<PROJECT_ID>:<REGION>:<INSTANCE_NAME>=tcp:127.0.0.1:3306", # TCP connection
                  # OR for Unix socket connection:
                  # "-instances=<PROJECT_ID>:<REGION>:<INSTANCE_NAME>=unix:/cloudsql/mysql.sock",
                  "-credential_file=/etc/cloudsql-proxy/credentials.json"]
        securityContext:
          runAsNonRoot: true # Recommended for security
        volumeMounts:
        - name: cloudsql-proxy-credentials
          mountPath: /etc/cloudsql-proxy
          readOnly: true
        # Mount the shared volume for Cloud SQL Proxy socket (if using Unix socket)
        - name: cloudsql-proxy-socket
          mountPath: /cloudsql

      volumes:
      - name: cloudsql-proxy-credentials # Volume for Cloud SQL Proxy service account key
        secret:
          secretName: cloudsql-proxy-credentials
      - name: cloudsql-proxy-socket # Shared emptyDir volume for Cloud SQL Proxy socket
        emptyDir: {}
      # Optional: Persistent Volume Claim if you need persistent storage
      # - name: laravel-storage
      #   persistentVolumeClaim:
      #     claimName: laravel-pvc
```
Replace placeholders: <PROJECT_ID>, <REGION>, <INSTANCE_NAME>.

- k8s/05-laravel-service.yaml: Exposes your application internally within the cluster.
```
apiVersion: v1
kind: Service
metadata:
  name: laravel-apache-service
  labels:
    app: laravel-apache
spec:
  selector:
    app: laravel-apache # Matches the label in your deployment
  ports:
    - protocol: TCP
      port: 80 # Service listens on port 80
      targetPort: 80 # Forwards traffic to container's port 80 (where Apache is running)
  type: ClusterIP # Internal service, use Ingress for external access
```
- k8s/06-laravel-ingress.yaml: Exposes your application to the internet using a Google Cloud Load Balancer.
```
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: laravel-apache-ingress
  annotations:
    # Use GKE's default Ingress controller for external Application Load Balancer
    kubernetes.io/ingress.class: "gce"
    # Optional: For automatic SSL certificate provisioning with Google-managed certificates
    networking.gke.io/managed-certificates: "laravel-apache-certificate" # Name of your ManagedCertificate resource
    # Optional: For redirecting HTTP to HTTPS
    ingress.gcp.kubernetes.io/pre-shared-cert: "REDIRECT_TO_HTTPS"
    # For a static external IP address (recommended for production)
    # kubernetes.io/ingress.global-static-ip-name: "laravel-static-ip"
  labels:
    app: laravel-apache
spec:
  rules:
  - host: your-domain.com # Replace with your actual domain
    http:
      paths:
      - path: /
        pathType: Prefix
        backend:
          service:
            name: laravel-apache-service # Name of your Kubernetes Service
            port:
              number: 80 # Port of your Kubernetes Service
  # Optional: TLS configuration (if not using managed certificates)
  # tls:
  # - hosts:
  #   - your-domain.com
  #   secretName: your-tls-secret # Secret containing your TLS certificate and key
```
Important: You'll need to create a ManagedCertificate resource if you want GKE to provision and manage SSL certificates for you.

- k8s/07-managed-certificate.yaml (Optional, but highly recommended for HTTPS):
```
apiVersion: networking.gke.io/v1
kind: ManagedCertificate
metadata:
  name: laravel-apache-certificate
spec:
  domains:
    - your-domain.com # Replace with your actual domain
```
- k8s/08-laravel-migration-job.yaml (Optional, for database migrations):
This ensures your database is up-to-date. Run this before your deployment or as a separate step in your CI/CD.
```
apiVersion: batch/v1
kind: Job
metadata:
  name: laravel-migrate-job
spec:
  template:
    metadata:
      labels:
        app: laravel-apache-migrate
    spec:
      restartPolicy: OnFailure
      serviceAccountName: cloudsql-proxy-sa # Use the same SA for Cloud SQL Proxy
      containers:
      - name: laravel-migrate
        image: gcr.io/<PROJECT_ID>/laravel-apache-app:latest # Your app image
        command: ["php", "/var/www/html/artisan", "migrate", "--force"]
        envFrom:
        - configMapRef:
            name: laravel-config
        - secretRef:
            name: laravel-secrets
        volumeMounts:
        - name: cloudsql-proxy-socket
          mountPath: /cloudsql
      - name: cloudsql-proxy # Sidecar for migrations to connect to DB
        image: gcr.io/cloudsql-docker/gce-proxy:latest
        command: ["/cloud_sql_proxy",
                  "-instances=<PROJECT_ID>:<REGION>:<INSTANCE_NAME>=tcp:127.0.0.1:3306",
                  "-credential_file=/etc/cloudsql-proxy/credentials.json"]
        securityContext:
          runAsNonRoot: true
        volumeMounts:
        - name: cloudsql-proxy-credentials
          mountPath: /etc/cloudsql-proxy
          readOnly: true
        - name: cloudsql-proxy-socket
          mountPath: /cloudsql
      volumes:
      - name: cloudsql-proxy-credentials
        secret:
          secretName: cloudsql-proxy-credentials
      - name: cloudsql-proxy-socket
        emptyDir: {}
```
6. Deploy to GKE

Navigate to your k8s/ directory and apply the manifests:
```
kubectl apply -f k8s/01-cloudsql-secret.yaml
kubectl apply -f k8s/02-laravel-configmap.yaml
kubectl apply -f k8s/03-laravel-secrets.yaml

# Run migrations (optional, but recommended before deployment)
kubectl apply -f k8s/08-laravel-migration-job.yaml
# You can check job status: kubectl get jobs
# And view logs: kubectl logs $(kubectl get pods -l app=laravel-apache-migrate -o jsonpath='{.items[0].metadata.name}')

kubectl apply -f k8s/04-laravel-deployment.yaml
kubectl apply -f k8s/05-laravel-service.yaml

# If using ManagedCertificate:
kubectl apply -f k8s/07-managed-certificate.yaml
# Wait for the certificate status to be PROVISIONING or ACTIVE
# kubectl get managedcertificate laravel-apache-certificate

kubectl apply -f k8s/06-laravel-ingress.yaml
```

7. Verify Deployment and Access Your Application:

- Check Pods:
```
kubectl get pods -l app=laravel-apache
```
Ensure your pods are in Running state.

- Check Deployment:
```
kubectl get deployments laravel-apache-app
```
- Check Services:
```
kubectl get services laravel-apache-service
```
- Check Ingress:
```
kubectl get ingress laravel-apache-ingress
```
Wait for the Ingress to get an external IP address (this might take several minutes as GKE provisions the load balancer). Once you see an IP under the ADDRESS column, update your domain's DNS A record to point to this IP. If you used a Managed Certificate, it might take longer to provision.

Deploying a Laravel application with Apache in Google Kubernetes Engine (GKE) is a robust way to host your application. It involves containerizing your Laravel app with Apache, pushing the image to a container registry, setting up a GKE cluster, configuring your database, defining Kubernetes resources, and finally, deploying your application.

Here's a comprehensive guide, building upon the previous discussions:

Prerequisites:

    Google Cloud Platform (GCP) Account: An active GCP account with billing enabled.

    Google Cloud SDK (gcloud CLI): Install and initialize the gcloud CLI on your local machine.

    kubectl: Install the Kubernetes command-line tool.

    Docker: Install Docker on your local machine to build container images.

    Laravel Application: Have your Laravel project ready.

    Git: For version control (recommended for your Laravel project).

Step-by-Step Deployment:

1. Containerize Your Laravel Application with Apache (Dockerfile)

Create a Dockerfile in the root of your Laravel project. This Dockerfile will build an image containing PHP, Apache, and your Laravel application.
Dockerfile

# Stage 1: Build dependencies (using Composer)
FROM composer:2 as composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Stage 2: Final application image with Apache
FROM php:8.2-apache # Or your preferred PHP version with Apache

WORKDIR /var/www/html

# Install system dependencies commonly required by Laravel and PHP extensions
# Update apt and install essential tools, image processing libs, etc.
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libxml2-dev \
    libonig-dev \
    libicu-dev \
    libzip-dev \
    # Add any other system libraries your specific Laravel app needs
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required by Laravel
RUN docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    # Add any other PHP extensions your app requires (e.g., redis, opcache)
    && a2enmod rewrite # Enable Apache's mod_rewrite for Laravel's clean URLs
    && a2enmod headers # Enable headers module if needed for CORS etc.

# Copy application code from your host to the container
COPY . .

# Copy composer dependencies from the build stage
COPY --from=composer /app/vendor /var/www/html/vendor

# Configure Apache virtual host to point to Laravel's public directory
# This command uses sed to modify the default Apache site configuration.
# It changes the DocumentRoot from /var/www/html to /var/www/html/public.
# It also ensures that .htaccess files are respected by setting AllowOverride All.
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && echo '<Directory /var/www/html/public>' >> /etc/apache2/apache2.conf \
    && echo '    AllowOverride All' >> /etc/apache2/apache2.conf \
    && echo '</Directory>' >> /etc/apache2/apache2.conf

# Set proper permissions for Laravel storage and cache directories
# This is crucial for Laravel to function correctly (logging, caching, sessions, uploads)
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Optimize Laravel for production (run these during build for smaller, faster images)
# Note: Ensure you don't call `env()` outside config files if using config:cache
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

# Expose port 80 (Apache's default HTTP port)
EXPOSE 80

# Apache is designed to run in the foreground by default in this base image
# CMD ["apache2-foreground"] # This is usually the default CMD and doesn't need to be explicitly added

2. Build and Push Docker Image to Google Container Registry (GCR) or Artifact Registry

    Authenticate Docker with GCP:
    Bash

gcloud auth configure-docker

Build your Docker image:
Bash

docker build -t gcr.io/<PROJECT_ID>/laravel-apache-app:latest .
# Replace <PROJECT_ID> with your actual GCP Project ID.

For production, consider using a specific version tag instead of latest (e.g., v1.0.0).

Push the image to GCR:
Bash

    docker push gcr.io/<PROJECT_ID>/laravel-apache-app:latest

3. Create a GKE Cluster

If you don't have a GKE cluster, create one. You can do this via the GCP Console or gcloud CLI.
Bash

gcloud container clusters create laravel-apache-cluster \
    --zone=<YOUR_ZONE> \
    --num-nodes=2 \
    --machine-type=e2-medium \
    --release-channel=stable # Recommended for production
    # Add other configurations like network policies, private cluster if needed

Replace <YOUR_ZONE> with a GCP zone near your users (e.g., us-central1-c, asia-southeast1-a).

    Get cluster credentials (connect kubectl to your cluster):
    Bash

    gcloud container clusters get-credentials laravel-apache-cluster --zone=<YOUR_ZONE>

4. Configure Database (Google Cloud SQL)

For a robust production setup, use Cloud SQL (e.g., MySQL, PostgreSQL).

    Create a Cloud SQL instance:

        Go to Navigation menu > SQL in the GCP Console.

        Click Create instance.

        Choose your desired database engine (MySQL is common for Laravel).

        Select a region (ideally the same as your GKE cluster).

        Set a root password and a database name for your Laravel app.

    Create a Service Account for Cloud SQL Proxy:
    You'll need a service account with the Cloud SQL Client role for your GKE pods to connect to Cloud SQL securely via the Cloud SQL Proxy.
    Bash

    gcloud iam service-accounts create cloudsql-proxy-sa --display-name="Cloud SQL Proxy Service Account"
    gcloud projects add-iam-policy-binding <PROJECT_ID> \
        --member="serviceAccount:cloudsql-proxy-sa@<PROJECT_ID>.iam.gserviceaccount.com" \
        --role="roles/cloudsql.client"

        Generate and Download JSON Key: Go to IAM & Admin > Service Accounts, find cloudsql-proxy-sa, click the three dots, select Manage keys > Add Key > Create new key > JSON. Save this JSON file securely on your local machine.

5. Define Kubernetes Manifests (YAML Files)

Create the following YAML files in a k8s/ directory within your Laravel project.

    k8s/01-cloudsql-secret.yaml: Store your Cloud SQL Proxy service account key securely as a Kubernetes Secret.
    Bash

# IMPORTANT: Replace /path/to/your/cloudsql-proxy-service-account-key.json
# with the actual path to the JSON key file you downloaded.
kubectl create secret generic cloudsql-proxy-credentials \
  --from-file=credentials.json=/path/to/your/cloudsql-proxy-service-account-key.json \
  --dry-run=client -o yaml > k8s/01-cloudsql-secret.yaml

(You can run the kubectl create secret command with --dry-run=client -o yaml to generate the YAML directly, or manually create it by base64 encoding the content of your JSON key.)

k8s/02-laravel-configmap.yaml: For non-sensitive environment variables.
YAML

apiVersion: v1
kind: ConfigMap
metadata:
  name: laravel-config
data:
  APP_ENV: production
  APP_DEBUG: "false"
  APP_URL: http://your-domain.com # Update after Ingress IP is known
  DB_CONNECTION: mysql
  # For Cloud SQL Proxy, connect via Unix socket or TCP loopback
  # Using Unix socket requires configuring Laravel's database.php
  DB_HOST: 127.0.0.1 # Cloud SQL Proxy listens on localhost (TCP)
  DB_PORT: "3306"
  DB_DATABASE: your_database_name # Your Cloud SQL database name
  BROADCAST_DRIVER: log
  CACHE_DRIVER: file # Consider 'redis' for better performance
  SESSION_DRIVER: file # Consider 'redis' for better performance
  QUEUE_CONNECTION: sync # Consider 'redis' or 'database' for queues
  # Add other non-sensitive Laravel environment variables here
  # For Laravel logs to go to stdout/stderr (visible in GKE logs), set:
  LOG_CHANNEL: stderr

Note: If your Laravel database.php is configured to use a Unix socket for MySQL, set DB_HOST to /cloudsql/<PROJECT_ID>:<REGION>:<INSTANCE_CONNECTION_NAME>. The INSTANCE_CONNECTION_NAME is found in Cloud SQL instance overview (e.g., your-project:your-region:your-instance-name).

k8s/03-laravel-secrets.yaml: For sensitive environment variables.
YAML

apiVersion: v1
kind: Secret
metadata:
  name: laravel-secrets
type: Opaque
data:
  # Base64 encode your values: echo -n "your_value" | base64
  APP_KEY: <base64_encoded_app_key> # php artisan key:generate, then base64 encode the output
  DB_USERNAME: <base64_encoded_db_username> # Your Cloud SQL DB username
  DB_PASSWORD: <base64_encoded_db_password> # Your Cloud SQL DB password
  # Add other sensitive Laravel environment variables here

k8s/04-laravel-deployment.yaml: Defines your application's pods.
YAML

apiVersion: apps/v1
kind: Deployment
metadata:
  name: laravel-apache-app
  labels:
    app: laravel-apache
spec:
  replicas: 2 # Number of instances (pods) for your application
  selector:
    matchLabels:
      app: laravel-apache
  template:
    metadata:
      labels:
        app: laravel-apache
    spec:
      # This links to the service account used by the Cloud SQL Proxy container
      serviceAccountName: cloudsql-proxy-sa
      containers:
      - name: laravel # Your main Laravel application container
        image: gcr.io/<PROJECT_ID>/laravel-apache-app:latest # Your Docker image
        ports:
        - containerPort: 80 # Apache listens on port 80
        envFrom:
        - configMapRef:
            name: laravel-config # Referencing the ConfigMap
        - secretRef:
            name: laravel-secrets # Referencing the Secret
        volumeMounts:
        # Mount the shared volume for Cloud SQL Proxy socket
        - name: cloudsql-proxy-socket
          mountPath: /cloudsql # Proxy will create socket here
        # Optional: If you need persistent storage for uploads etc.
        # - name: laravel-storage
        #   mountPath: /var/www/html/storage/app/public # Or wherever Laravel stores persistent files
        #   subPath: app/public # Use subPath to mount a subdirectory of the PVC
        # Add liveness and readiness probes for health checks
        livenessProbe:
          httpGet:
            path: /health # Or your Laravel health check endpoint (e.g., /up)
            port: 80
          initialDelaySeconds: 30
          periodSeconds: 10
        readinessProbe:
          httpGet:
            path: /health
            port: 80
          initialDelaySeconds: 15
          periodSeconds: 5

      - name: cloudsql-proxy # Sidecar container for secure Cloud SQL connection
        image: gcr.io/cloudsql-docker/gce-proxy:latest
        command: ["/cloud_sql_proxy",
                  "-instances=<PROJECT_ID>:<REGION>:<INSTANCE_NAME>=tcp:127.0.0.1:3306", # TCP connection
                  # OR for Unix socket connection:
                  # "-instances=<PROJECT_ID>:<REGION>:<INSTANCE_NAME>=unix:/cloudsql/mysql.sock",
                  "-credential_file=/etc/cloudsql-proxy/credentials.json"]
        securityContext:
          runAsNonRoot: true # Recommended for security
        volumeMounts:
        - name: cloudsql-proxy-credentials
          mountPath: /etc/cloudsql-proxy
          readOnly: true
        # Mount the shared volume for Cloud SQL Proxy socket (if using Unix socket)
        - name: cloudsql-proxy-socket
          mountPath: /cloudsql

      volumes:
      - name: cloudsql-proxy-credentials # Volume for Cloud SQL Proxy service account key
        secret:
          secretName: cloudsql-proxy-credentials
      - name: cloudsql-proxy-socket # Shared emptyDir volume for Cloud SQL Proxy socket
        emptyDir: {}
      # Optional: Persistent Volume Claim if you need persistent storage
      # - name: laravel-storage
      #   persistentVolumeClaim:
      #     claimName: laravel-pvc

Replace placeholders: <PROJECT_ID>, <REGION>, <INSTANCE_NAME>.

k8s/05-laravel-service.yaml: Exposes your application internally within the cluster.
YAML

apiVersion: v1
kind: Service
metadata:
  name: laravel-apache-service
  labels:
    app: laravel-apache
spec:
  selector:
    app: laravel-apache # Matches the label in your deployment
  ports:
    - protocol: TCP
      port: 80 # Service listens on port 80
      targetPort: 80 # Forwards traffic to container's port 80 (where Apache is running)
  type: ClusterIP # Internal service, use Ingress for external access

k8s/06-laravel-ingress.yaml: Exposes your application to the internet using a Google Cloud Load Balancer.
YAML

apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: laravel-apache-ingress
  annotations:
    # Use GKE's default Ingress controller for external Application Load Balancer
    kubernetes.io/ingress.class: "gce"
    # Optional: For automatic SSL certificate provisioning with Google-managed certificates
    networking.gke.io/managed-certificates: "laravel-apache-certificate" # Name of your ManagedCertificate resource
    # Optional: For redirecting HTTP to HTTPS
    ingress.gcp.kubernetes.io/pre-shared-cert: "REDIRECT_TO_HTTPS"
    # For a static external IP address (recommended for production)
    # kubernetes.io/ingress.global-static-ip-name: "laravel-static-ip"
  labels:
    app: laravel-apache
spec:
  rules:
  - host: your-domain.com # Replace with your actual domain
    http:
      paths:
      - path: /
        pathType: Prefix
        backend:
          service:
            name: laravel-apache-service # Name of your Kubernetes Service
            port:
              number: 80 # Port of your Kubernetes Service
  # Optional: TLS configuration (if not using managed certificates)
  # tls:
  # - hosts:
  #   - your-domain.com
  #   secretName: your-tls-secret # Secret containing your TLS certificate and key

Important: You'll need to create a ManagedCertificate resource if you want GKE to provision and manage SSL certificates for you.

    k8s/07-managed-certificate.yaml (Optional, but highly recommended for HTTPS):
    YAML

    apiVersion: networking.gke.io/v1
    kind: ManagedCertificate
    metadata:
      name: laravel-apache-certificate
    spec:
      domains:
        - your-domain.com # Replace with your actual domain

k8s/08-laravel-migration-job.yaml (Optional, for database migrations):
This ensures your database is up-to-date. Run this before your deployment or as a separate step in your CI/CD.
YAML

    apiVersion: batch/v1
    kind: Job
    metadata:
      name: laravel-migrate-job
    spec:
      template:
        metadata:
          labels:
            app: laravel-apache-migrate
        spec:
          restartPolicy: OnFailure
          serviceAccountName: cloudsql-proxy-sa # Use the same SA for Cloud SQL Proxy
          containers:
          - name: laravel-migrate
            image: gcr.io/<PROJECT_ID>/laravel-apache-app:latest # Your app image
            command: ["php", "/var/www/html/artisan", "migrate", "--force"]
            envFrom:
            - configMapRef:
                name: laravel-config
            - secretRef:
                name: laravel-secrets
            volumeMounts:
            - name: cloudsql-proxy-socket
              mountPath: /cloudsql
          - name: cloudsql-proxy # Sidecar for migrations to connect to DB
            image: gcr.io/cloudsql-docker/gce-proxy:latest
            command: ["/cloud_sql_proxy",
                      "-instances=<PROJECT_ID>:<REGION>:<INSTANCE_NAME>=tcp:127.0.0.1:3306",
                      "-credential_file=/etc/cloudsql-proxy/credentials.json"]
            securityContext:
              runAsNonRoot: true
            volumeMounts:
            - name: cloudsql-proxy-credentials
              mountPath: /etc/cloudsql-proxy
              readOnly: true
            - name: cloudsql-proxy-socket
              mountPath: /cloudsql
          volumes:
          - name: cloudsql-proxy-credentials
            secret:
              secretName: cloudsql-proxy-credentials
          - name: cloudsql-proxy-socket
            emptyDir: {}

6. Deploy to GKE

Navigate to your k8s/ directory and apply the manifests:
Bash

kubectl apply -f k8s/01-cloudsql-secret.yaml
kubectl apply -f k8s/02-laravel-configmap.yaml
kubectl apply -f k8s/03-laravel-secrets.yaml

# Run migrations (optional, but recommended before deployment)
kubectl apply -f k8s/08-laravel-migration-job.yaml
# You can check job status: kubectl get jobs
# And view logs: kubectl logs $(kubectl get pods -l app=laravel-apache-migrate -o jsonpath='{.items[0].metadata.name}')

kubectl apply -f k8s/04-laravel-deployment.yaml
kubectl apply -f k8s/05-laravel-service.yaml

# If using ManagedCertificate:
kubectl apply -f k8s/07-managed-certificate.yaml
# Wait for the certificate status to be PROVISIONING or ACTIVE
# kubectl get managedcertificate laravel-apache-certificate

kubectl apply -f k8s/06-laravel-ingress.yaml

7. Verify Deployment and Access Your Application:

    Check Pods:
    Bash

kubectl get pods -l app=laravel-apache

Ensure your pods are in Running state.

Check Deployment:
Bash

kubectl get deployments laravel-apache-app

Check Services:
Bash

kubectl get services laravel-apache-service

Check Ingress:
Bash

    kubectl get ingress laravel-apache-ingress

    Wait for the Ingress to get an external IP address (this might take several minutes as GKE provisions the load balancer). Once you see an IP under the ADDRESS column, update your domain's DNS A record to point to this IP. If you used a Managed Certificate, it might take longer to provision.

8. Important Considerations & Best Practices:

- Session and Cache Drivers: Change Laravel's SESSION_DRIVER and CACHE_DRIVER from file to redis (using Google Cloud Memorystore for Redis) or database. Pods are ephemeral, so file-based sessions/cache will be lost on pod restarts.
- Persistent Storage (PV/PVC): If your Laravel application requires storing files (e.g., user uploads) that must persist across pod restarts, you must use Persistent Volumes (PV) and Persistent Volume Claims (PVC).

- Example k8s/09-pvc.yaml:
```
apiVersion: v1
kind: PersistentVolumeClaim
metadata:
  name: laravel-storage-pvc
spec:
  accessModes:
    - ReadWriteOnce # Can be mounted by one node read-write
  resources:
    requests:
      storage: 5Gi # Request 5 GB of storage
          storage: 5Gi # Request 5 GB of storage
```
- Then, update your deployment.yaml to include the volumeMounts and volumes as shown in the commented-out sections of the deployment.yaml example.

- Logging: By setting LOG_CHANNEL=stderr in your ConfigMap, Laravel logs will be sent to the container's standard error output, which GKE automatically collects and sends to Google Cloud Logging. You can view them in the GCP Console under "Logs Explorer."

- Monitoring: GKE integrates with Google Cloud Monitoring. Configure health checks in your deployment (liveness and readiness probes) to ensure traffic is only sent to healthy pods.

- Autoscaling: Use Kubernetes Horizontal Pod Autoscaler (HPA) to automatically scale the number of pods based on CPU utilization or other metrics.
```
kubectl autoscale deployment laravel-apache-app --cpu-percent=80 --min=2 --max=10
```
- CI/CD: Implement a CI/CD pipeline (e.g., Google Cloud Build, GitHub Actions, GitLab CI/CD) to automate the build, push, and deployment process. This will ensure faster and more reliable updates.

- HTTPS: Always use HTTPS in production. The Ingress with Managed Certificates simplifies this greatly.

- Security:

  - Use specific image tags (not latest) for production.
  - Regularly scan your Docker images for vulnerabilities.
  - Follow the principle of least privilege for service accounts.
  - Keep Laravel and its dependencies updated.
  - Ensure your .env file values are securely stored in Kubernetes Secrets.

By following these steps, you'll have a scalable, reliable, and secure Laravel application running on GKE with Apache.
