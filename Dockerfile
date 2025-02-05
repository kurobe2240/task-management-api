FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src

# Copy csproj and restore dependencies
COPY ["TaskManagement.API/TaskManagement.API.csproj", "TaskManagement.API/"]
RUN dotnet restore "TaskManagement.API/TaskManagement.API.csproj"

# Copy everything else and build
COPY . .
RUN dotnet build "TaskManagement.API/TaskManagement.API.csproj" -c Release -o /app/build

# Publish
FROM build AS publish
RUN dotnet publish "TaskManagement.API/TaskManagement.API.csproj" -c Release -o /app/publish

# Final stage/image
FROM mcr.microsoft.com/dotnet/aspnet:8.0
WORKDIR /app
COPY --from=publish /app/publish .
EXPOSE 8080
ENV ASPNETCORE_URLS=http://+:8080
ENTRYPOINT ["dotnet", "TaskManagement.API.dll"] 