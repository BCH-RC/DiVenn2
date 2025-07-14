# Use Ubuntu as the base image
FROM ubuntu:22.04

# Install system dependencies
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y \
    tzdata \
    curl \
    build-essential \
    libcurl4-openssl-dev \
    libssl-dev \
    libxml2-dev \
    gfortran \
    libreadline-dev \
    libxt-dev \
    libpng-dev \
    libjpeg-dev \
    libtiff-dev \
    libcairo2-dev \
    libbz2-dev \
    liblzma-dev \
    xz-utils \
    software-properties-common \
    && rm -rf /var/lib/apt/lists/*

# Install Python 3.11 and required libraries
RUN add-apt-repository ppa:deadsnakes/ppa && \
    apt-get update && \
    DEBIAN_FRONTEND=noninteractive apt-get install -y \
    python3.11 \
    python3.11-venv \
    python3.11-dev \
    python3.11-distutils \
    && rm -rf /var/lib/apt/lists/*

# Set Python 3.11 as default
RUN update-alternatives --install /usr/bin/python3 python3 /usr/bin/python3.11 1

# Set environment variables
ENV R_VERSION=4.4.0 \
    CFLAGS='-std=gnu99' \
    R_KEEP_PKG_SOURCE=yes

# Install R from source
RUN curl -O https://cran.r-project.org/src/base/R-4/R-${R_VERSION}.tar.gz && \
    tar -xf R-${R_VERSION}.tar.gz && \
    cd R-${R_VERSION} && \
    ./configure --with-x=no --enable-R-shlib && \
    make -j$(nproc) && \
    make install && \
    cd .. && rm -rf R-${R_VERSION}*

# Set CRAN Mirror
RUN echo 'options(repos = c(CRAN = "https://cloud.r-project.org"))' >> /usr/local/lib/R/etc/Rprofile.site

# Install R packages from CRAN and Bioconductor
RUN R -e "install.packages(c('optparse', 'Seurat', 'BiocManager'), repos = 'https://cloud.r-project.org')" && \
    R -e "BiocManager::install(c('limma', 'MAST', 'DESeq2'), update = TRUE, ask = FALSE)"

# Install immunogenomics/presto for faster Wilcoxon Rank Sum Test
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y \
    git \
    pkg-config \
    libharfbuzz-dev \
    libfribidi-dev \
    libfreetype6-dev \
    libgit2-dev \
    && rm -rf /var/lib/apt/lists/*

RUN R -e "install.packages(c('devtools'), repos = 'https://cloud.r-project.org')"
RUN R -e "devtools::install_github('immunogenomics/presto')"

# Create and use a Python 3.11 virtual environment
RUN python3.11 -m venv /venv
ENV PATH="/venv/bin:$PATH"

# Install Python dependencies
RUN pip install --upgrade pip && \
    pip install scanpy pandas numpy argparse

# Set working directory
WORKDIR /usr/src/app

# Copy the scripts into the container
COPY Preprocessing_Seuratobj.r /usr/src/app/
COPY Preprocessing_h5ad.py /usr/src/app/
COPY run_preprocessing.sh /usr/src/app/

# Make wrapper script executable
RUN chmod +x /usr/src/app/run_preprocessing.sh

# Set wrapper script as the default entrypoint
ENTRYPOINT ["/usr/src/app/run_preprocessing.sh"]
