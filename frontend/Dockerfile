# =============================================================================
# Dockerfile for IPMS Frontend (Development)
# =============================================================================
# This Dockerfile sets up a Node.js environment for Vue.js 3 development
# using Vite's built-in dev server with Hot Module Replacement (HMR).
#
# Build context: frontend/
# =============================================================================

FROM node:22-alpine

# Set working directory
WORKDIR /app

# Install dependencies for file watching (optional but recommended)
RUN apk add --no-cache git

# Copy package files
COPY package*.json ./

# Install all dependencies (including devDependencies)
RUN npm install

# Copy the rest of the application
COPY . .

# Expose Vite's default dev server port
EXPOSE 5173

# Start Vite dev server
# --host 0.0.0.0 allows connections from outside the container
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
